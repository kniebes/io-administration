<?php declare(strict_types=1);

namespace App\Service\Image\Upload\Uploader;

use App\Service\Image\Upload\Uploader\Abstract\AbstractUploader;
use Imagick;
use ImagickException;

class WebpUploader extends AbstractUploader
{
    private const int QUALITY = 80;

    public function supports(?string $mimeType = null): bool
    {
        return $mimeType === 'image/webp';
    }

    /**
     * @throws ImagickException
     */
    protected function configureOutput(Imagick $image): void
    {
        $image->setImageFormat('webp');
        $image->setImageCompressionQuality(self::QUALITY);
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
