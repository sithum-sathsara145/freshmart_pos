<?php

namespace App\Services;

use App\Models\Setting;
use Cloudinary\Api\ApiUtils;
use Cloudinary\Cloudinary;

class CloudinaryService
{
    private Cloudinary $cloudinary;
    private ?string $cloudName;
    private ?string $apiKey;
    private ?string $apiSecret;
    private string $folder;

    public function __construct()
    {
        // Prefer credentials saved in Settings (API keys tab); fall back to .env config.
        $this->cloudName = $this->setting('cloudinary_cloud_name') ?: config('services.cloudinary.cloud_name');
        $this->apiKey    = $this->setting('cloudinary_api_key', true) ?: config('services.cloudinary.api_key');
        $this->apiSecret = $this->setting('cloudinary_api_secret', true) ?: config('services.cloudinary.api_secret');
        $this->folder    = $this->setting('cloudinary_folder') ?: config('services.cloudinary.folder', 'products');

        $this->cloudinary = new Cloudinary([
            'cloud' => [
                'cloud_name' => $this->cloudName,
                'api_key'    => $this->apiKey,
                'api_secret' => $this->apiSecret,
            ],
            'url' => ['secure' => true],
        ]);
    }

    /** Read a saved setting defensively (DB may be unavailable during install/migrations). */
    private function setting(string $key, bool $secret = false): ?string
    {
        try {
            return $secret ? Setting::getSecret($key) : Setting::get($key);
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function configured(): bool
    {
        return (bool) ($this->cloudName && $this->apiKey && $this->apiSecret);
    }

    /**
     * Build signed parameters so the browser can upload directly to Cloudinary.
     * The API secret never leaves the server — only the resulting signature does.
     *
     * @throws \RuntimeException when not configured.
     */
    public function signedUploadParams(): array
    {
        if (! $this->configured()) {
            throw new \RuntimeException('Image service is not configured. Please add your Cloudinary credentials.');
        }

        $timestamp = time();
        $folder    = $this->folder;

        // Only timestamp + folder are signed; neither value contains & or =, so the
        // signature is identical across signature versions 1 and 2.
        $signature = ApiUtils::signParameters(
            ['folder' => $folder, 'timestamp' => $timestamp],
            (string) $this->apiSecret
        );

        return [
            'cloud_name' => $this->cloudName,
            'api_key'    => $this->apiKey,
            'timestamp'  => $timestamp,
            'folder'     => $folder,
            'signature'  => $signature,
        ];
    }

    /**
     * Upload an image file; returns ['url' => secure url, 'public_id' => id].
     *
     * @throws \RuntimeException with a user-friendly message when the upload is rejected.
     */
    public function upload(string $path): array
    {
        if (! $this->configured()) {
            throw new \RuntimeException('Image service is not configured. Please add your Cloudinary credentials before uploading images.');
        }

        try {
            $res = $this->cloudinary->uploadApi()->upload($path, [
                'folder'        => $this->folder,
                'resource_type' => 'image',
            ]);
        } catch (\Throwable $e) {
            // Cloudinary refused the upload — invalid/corrupt image, quota, auth, network, etc.
            throw new \RuntimeException('Cloudinary did not accept the image: ' . $e->getMessage(), 0, $e);
        }

        if (empty($res['secure_url'])) {
            throw new \RuntimeException('Cloudinary did not return an image URL. Please try again.');
        }

        return ['url' => $res['secure_url'], 'public_id' => $res['public_id']];
    }

    /** Best-effort delete; never throws. */
    public function delete(?string $publicId): void
    {
        if (! $publicId) {
            return;
        }
        try {
            $this->cloudinary->uploadApi()->destroy($publicId);
        } catch (\Throwable $e) {
            // ignore — a failed cleanup shouldn't block the request
        }
    }
}
