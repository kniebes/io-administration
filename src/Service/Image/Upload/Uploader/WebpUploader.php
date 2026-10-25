<?php declare(strict_types=1);

namespace App\Service\Image\Upload\Uploader;

use App\Service\Image\Upload\Uploader\Abstract\AbstractUploader;

class WebpUploader extends AbstractUploader
{
    public function supports(?string $mimeType = null): bool
    {
        return $mimeType === 'image/webp';
    }

    protected function createImageFrom(string $path): \GdImage|false
    {
        return imagecreatefromwebp($path);
    }

    protected function writeImage(\GdImage $image, string $path): bool
    {
        return imagewebp($image, $path);
    }

    protected function getImageFileNameExtension(): string
    {
        return 'webp';
    }

    protected function generateExif(string $filename): array
    {
        return [];
    }
}
