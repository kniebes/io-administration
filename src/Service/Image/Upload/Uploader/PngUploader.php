<?php declare(strict_types=1);

namespace App\Service\Image\Upload\Uploader;

use App\Service\Image\Upload\Uploader\Abstract\AbstractUploader;

class PngUploader extends AbstractUploader
{
    public function supports(?string $mimeType = null): bool
    {
        return $mimeType === 'image/png';
    }

    protected function createImageFrom(string $path): \GdImage|false
    {
        return imagecreatefrompng($path);
    }

    protected function writeImage(\GdImage $image, string $path): bool
    {
        return imagepng($image, $path);
    }

    protected function getImageFileNameExtension(): string
    {
        return 'png';
    }

    protected function generateExif(string $filename): array
    {
        return [];
    }
}
