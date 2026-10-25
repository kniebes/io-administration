<?php declare(strict_types=1);

namespace App\Service\Image\Upload\Uploader\Abstract;

use App\Entity\Image;
use App\Entity\ImageExif;
use App\Entity\ImageVersion;
use App\Enum\ImageExifLabel;
use App\Service\Image\ImageService;
use App\Service\Image\Upload\ExifParser;
use App\Service\Image\Upload\Uploader\Exception\ImageSizeException;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

#[AutoconfigureTag('app.image_uploader')]
abstract class AbstractUploader
{
    protected readonly string $photoBasePath;

    public function __construct(
        protected readonly EntityManagerInterface $entityManager,
        protected readonly SluggerInterface $slugger,
        protected readonly ExifParser $exifParser,
        #[Autowire(env: 'PHOTO_DOMAIN_PATH')] protected readonly string $photoDomainPath,
        #[Autowire(env: 'PHOTO_HOST')] protected readonly string $photoHost,
    ) {
        $this->photoBasePath = $photoDomainPath . '/photo';
    }

    abstract public function supports(?string $mimeType = null): bool;

    abstract protected function createImageFrom(string $path): \GdImage|false;

    abstract protected function writeImage(\GdImage $image, string $path): bool;

    abstract protected function getImageFileNameExtension(): string;

    protected function generateExif(string $filename): array
    {
        return $this->exifParser->generateExif($filename);
    }

    public function upload(UploadedFile $uploadedFile): Image
    {
        $targetDir = $this->getTargetDir();
        $targetFilename = $this->prepareFilename(filename: $uploadedFile->getClientOriginalName(), targetDir: $targetDir);
        $file = $uploadedFile->move($targetDir, $targetFilename);
        $metrics = $this->getImageMetrics($file);
        $exif = $this->generateExif($file->getPathname());

        $title = trim((string) ($exif['title'] ?? ''));
        if ($title === '') {
            $title = pathinfo($file->getFilename(), PATHINFO_FILENAME);
        }

        $image = new Image()
            ->setDate(new DateTimeImmutable())
            ->setHost($this->photoHost)
            ->setUrl($this->convertToUrl($file->getPathname()))
            ->setMimeType($file->getMimeType() ?? '')
            ->setWidth($metrics['width'])
            ->setHeight($metrics['height'])
            ->setByteSize($metrics['byteSize'])
            ->setAspectRatio($metrics['aspectRatio'])
            ->setTitle($title);

        foreach ($this->createVersions($file) as $imageVersion) {
            $image->addImageVersion($imageVersion);
        }

        foreach ($exif as $key => $value) {
            $label = $this->matchExifLabel((string) $key);
            $value = trim((string) $value);

            if ($label === null || $value === '' || $value === 'ISO') {
                continue;
            }

            $image->addExif(
                new ImageExif()
                    ->setLabel($label)
                    ->setValue($value)
            );
        }

        $this->entityManager->persist($image);
        $this->entityManager->flush();

        return $image;
    }

    protected function getImageMetrics(File $file): array
    {
        [$width, $height] = getimagesize($file->getPathname());

        return [
            'width' => $width,
            'height' => $height,
            'byteSize' => (int) filesize($file->getPathname()),
            'aspectRatio' => round($width / $height, 2),
        ];
    }

    /**
     * @return list<ImageVersion>
     */
    protected function createVersions(File $file): array
    {
        $versions = [];
        foreach (ImageService::RESOLUTION as $length) {
            try {
                $versionPath = $this->resizeImage(path: $file->getPathname(), length: $length);
            } catch (ImageSizeException) {
                return [];
            } catch (Exception) {
                continue;
            }

            $metrics = $this->getImageMetrics(new File($versionPath));
            $versions[] = new ImageVersion()
                ->setVersionIdentifier((string) $length)
                ->setUrl($this->convertToUrl($versionPath))
                ->setWidth($metrics['width'])
                ->setHeight($metrics['height'])
                ->setByteSize($metrics['byteSize'])
                ->setAspectRatio($metrics['aspectRatio']);
        }

        return $versions;
    }

    /**
     * @throws ImageSizeException|Exception
     */
    protected function resizeImage(string $path, int $length): string
    {
        $image = $this->createImageFrom($path);
        $width = imagesx($image);
        $height = imagesy($image);
        if ($width <= 1280 && $height <= 1280) {
            throw new ImageSizeException('The image is too small');
        }

        $ratio = $width / $height;
        $newWidth = (int) $length;
        $newHeight = (int) ($newWidth / $ratio);
        $measure = $width;

        if ($measure < $length) {
            throw new Exception(sprintf('Image is %dx%d and will not be resized to %d!', $width, $height, $length));
        }

        $newImage = imagecreatetruecolor($newWidth, $newHeight);
        imagealphablending($newImage, false);
        imagesavealpha($newImage, true);
        imagecopyresampled($newImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        $versionFilename = $this->getVersionFilename($path, $newWidth, $newHeight);
        if (!$this->writeImage($newImage, $versionFilename)) {
            throw new Exception(sprintf('imageVersion %d was not created!', $length));
        }

        return $versionFilename;
    }

    protected function convertToUrl(string $filepath): string
    {
        return str_replace($this->photoDomainPath, '', $filepath);
    }

    protected function getTargetDir(): string
    {
        $path = date('Y/m/d');
        $dir = '';
        $baseDir = $this->photoBasePath;
        foreach(explode('/', $path) as $part) {
            if (!is_dir($baseDir. ($dir .= "/$part"))) {
                mkdir($baseDir.$dir);
            }
        }

        return sprintf('%s/%s', $baseDir, $path);
    }

    protected function getVersionFilename(string $targetFilename, int $width, int $height): string
    {
        $basename = basename($targetFilename);
        $versionFilename = sprintf(
            '%s-%dx%d.%s',
            substr($basename, 0, strrpos($basename, '.')),
            $width,
            $height,
            $this->getImageFileNameExtension()
        );

        return str_replace($basename, '', $targetFilename).$versionFilename;
    }

    protected function prepareFilename(string $filename, string $targetDir): string
    {
        $stem = $this->slugger->slug(pathinfo($filename, PATHINFO_FILENAME))->lower()->toString();
        if ($stem === '') {
            $stem = 'image';
        }

        $extension = $this->getImageFileNameExtension();
        $candidate = $stem . '.' . $extension;
        $counter = 1;

        while (file_exists($targetDir . '/' . $candidate)) {
            $candidate = $stem . '-' . $counter . '.' . $extension;
            $counter++;
        }

        return $candidate;
    }

    private function matchExifLabel(string $key): ?ImageExifLabel
    {
        return match ($key) {
            'model' => ImageExifLabel::Model,
            'lens' => ImageExifLabel::Lens,
            'focallength' => ImageExifLabel::FocalLength,
            'aperture' => ImageExifLabel::Aperture,
            'exposure' => ImageExifLabel::ExposureTime,
            'iso' => ImageExifLabel::ISO,
            default => null,
        };
    }
}
