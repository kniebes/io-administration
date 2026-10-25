<?php declare(strict_types=1);

namespace App\Service\Image\Upload\Uploader;

use App\Service\Image\ImageService;
use App\Service\Image\Upload\Uploader\Abstract\AbstractUploader;
use Imagick;
use ImagickException;

class JpegUploader extends AbstractUploader
{
    private const int QUALITY = 90;

    public function supports(?string $mimeType = null): bool
    {
        return in_array($mimeType, ['image/jpeg', 'image/jpg'], true);
    }

    /**
     * @throws ImagickException
     */
    protected function configureRead(Imagick $image): void
    {
        $maxLength = max(ImageService::RESOLUTION);
        $image->setOption(key: 'jpeg:size', value: $maxLength . 'x' . $maxLength);
    }

    /**
     * @throws ImagickException
     */
    protected function configureOutput(Imagick $image): void
    {
        $image->setImageFormat('jpeg');
        $image->setImageCompressionQuality(self::QUALITY);
    }

    protected function getImageFileNameExtension(): string
    {
        return 'jpeg';
    }
}
