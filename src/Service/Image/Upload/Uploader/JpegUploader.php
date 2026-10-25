<?php declare(strict_types=1);

namespace App\Service\Image\Upload\Uploader;

use App\Service\Image\Upload\Uploader\Abstract\AbstractUploader;

class JpegUploader extends AbstractUploader
{
    private const int QUALITY = 90;

    public function supports(?string $mimeType = null): bool
    {
        return in_array($mimeType, ['image/jpeg', 'image/jpg'], true);
    }

    protected function createImageFrom(string $path): \GdImage|false
    {
        return imagecreatefromjpeg($path);
    }

    protected function writeImage(\GdImage $image, string $path): bool
    {
        return imagejpeg($image, $path, self::QUALITY);
    }

    protected function getImageFileNameExtension(): string
    {
        return 'jpeg';
    }
}
