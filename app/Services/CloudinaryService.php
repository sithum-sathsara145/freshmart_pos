<?php

namespace App\Services;

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

    /** Upload an image file; returns ['url' => secure url, 'public_id' => id]. */
    public function upload(string $path): array
    {
        $res = $this->cloudinary->uploadApi()->upload($path, [
            'folder'        => config('services.cloudinary.folder', 'products'),
            'resource_type' => 'image',
        ]);

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
