<?php

namespace App\Services;

use Cloudinary\Api\ApiUtils;
use Cloudinary\Cloudinary;

class CloudinaryService
{
    private Cloudinary $cloudinary;

    public function __construct()
    {
        $this->cloudinary = new Cloudinary([
            'cloud' => [
                'cloud_name' => config('services.cloudinary.cloud_name'),
                'api_key'    => config('services.cloudinary.api_key'),
                'api_secret' => config('services.cloudinary.api_secret'),
            ],
            'url' => ['secure' => true],
        ]);
    }

    public function configured(): bool
    {
        return (bool) (config('services.cloudinary.cloud_name')
            && config('services.cloudinary.api_key')
            && config('services.cloudinary.api_secret'));
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
        $folder    = config('services.cloudinary.folder', 'products');

        // Only timestamp + folder are signed; neither value contains & or =, so the
        // signature is identical across signature versions 1 and 2.
        $signature = ApiUtils::signParameters(
            ['folder' => $folder, 'timestamp' => $timestamp],
            (string) config('services.cloudinary.api_secret')
        );

        return [
            'cloud_name' => config('services.cloudinary.cloud_name'),
            'api_key'    => config('services.cloudinary.api_key'),
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
                'folder'        => config('services.cloudinary.folder', 'products'),
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
