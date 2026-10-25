<?php declare(strict_types=1);

namespace App\Service\Image\Upload\Uploader;

use App\Service\Image\Upload\Uploader\Abstract\AbstractUploader;
use Imagick;
use ImagickException;

class PngUploader extends AbstractUploader
{
    public function supports(?string $mimeType = null): bool
    {
        return $mimeType === 'image/png';
    }

    /**
     * @throws ImagickException
     */
    protected function configureOutput(Imagick $image): void
    {
        $image->setImageFormat('png');
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
