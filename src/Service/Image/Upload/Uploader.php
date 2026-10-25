<?php declare(strict_types=1);

namespace App\Service\Image\Upload;

use App\Entity\Image;
use App\Service\Image\Upload\Uploader\Abstract\AbstractUploader;
use RuntimeException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

readonly class Uploader
{
    /**
     * @param iterable<AbstractUploader> $handlers
     */
    public function __construct(
        private iterable $handlers
    )
    {
    }

    public function upload(UploadedFile $uploadedFile): Image
    {
        $mimeType = $uploadedFile->getMimeType();
        foreach ($this->handlers as $handler) {
            if ($handler->supports($mimeType)) {
                return $handler->upload($uploadedFile);
            }
        }

        throw new RuntimeException('MimeType '.$mimeType.' not supported!');
    }
}
