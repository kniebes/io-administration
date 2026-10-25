<?php declare(strict_types=1);

namespace App\Service\Image\Upload\Uploader\Abstract;

use App\Entity\Image;
use App\Entity\ImageExif;
use App\Entity\ImageVersion;
use App\Enum\ImageExifLabel;
use App\Service\ErrorLogger\Interface\ErrorLoggerInterface;
use App\Service\Image\ImageService;
use App\Service\Image\Upload\ExifParser;
use App\Service\Image\Upload\Uploader\Exception\ImageSizeException;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Imagick;
use ImagickException;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;
use Throwable;

#[AutoconfigureTag('app.image_uploader')]
abstract class AbstractUploader
{
    protected readonly string $photoBasePath;

    public function __construct(
        protected readonly EntityManagerInterface $entityManager,
        protected readonly SluggerInterface $slugger,
        protected readonly ExifParser $exifParser,
        protected readonly ErrorLoggerInterface $errorLogger,
        #[Autowire(env: 'PHOTO_DOMAIN_PATH')] protected readonly string $photoDomainPath,
        #[Autowire(env: 'PHOTO_HOST')] protected readonly string $photoHost,
    ) {
        $this->photoBasePath = $photoDomainPath . '/photo';
    }

    abstract public function supports(?string $mimeType = null): bool;

    /**
     * @throws ImagickException
     */
    abstract protected function configureOutput(Imagick $image): void;

    abstract protected function getImageFileNameExtension(): string;

    /**
     * @throws ImagickException
     */
    protected function configureRead(Imagick $image): void
    {
    }

    protected function generateExif(string $filename): array
    {
        return $this->exifParser->generateExif($filename);
    }

    public function upload(UploadedFile $uploadedFile): ?Image
    {
        $file = null;
        $image = null;

        try {
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

            if ($metrics['width'] >= 1280) {
                $sourceImage = $this->readImage($file->getPathname());
                foreach (ImageService::RESOLUTION as $length) {
                    if ($metrics['width'] < $length) {
                        continue;
                    }

                    try {
                        $image->addImageVersion($this->createVersion(sourceImage: $sourceImage, file: $file, length: $length));
                    } catch (ImagickException $exception) {
                        $this->errorLogger->log($exception, ErrorLoggerInterface::TYPE_LOG);
                    }
                }
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
        } catch (Throwable $throwable) {
            $this->errorLogger->log($throwable);
            $this->removeWrittenFiles(file: $file, image: $image);

            return null;
        }

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
     * @throws ImagickException
     */
    protected function createVersion(Imagick $sourceImage, File $file, int $length): ImageVersion
    {
        $versionImage = clone $sourceImage;
        $versionImage->resizeImage(columns: $length, rows: 0, filter: Imagick::FILTER_LANCZOS, blur: 1);
        $this->configureOutput($versionImage);

        $versionPath = $this->getVersionFilename(
            targetFilename: $file->getPathname(),
            width: $versionImage->getImageWidth(),
            height: $versionImage->getImageHeight(),
        );
        $versionImage->writeImage($versionPath);

        $metrics = $this->getImageMetrics(new File($versionPath));

        return new ImageVersion()
            ->setVersionIdentifier((string) $length)
            ->setUrl($this->convertToUrl($versionPath))
            ->setWidth($metrics['width'])
            ->setHeight($metrics['height'])
            ->setByteSize($metrics['byteSize'])
            ->setAspectRatio($metrics['aspectRatio']);
    }

    /**
     * @throws ImagickException
     */
    protected function readImage(string $path): Imagick
    {
        $image = new Imagick();
        $this->configureRead($image);
        $image->readImage($path);

        $iccProfiles = $image->getImageProfiles('icc');
        $image->stripImage();
        if (isset($iccProfiles['icc'])) {
            $image->profileImage(name: 'icc', profile: $iccProfiles['icc']);
        }

        return $image;
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

    protected function removeWrittenFiles(?File $file, ?Image $image): void
    {
        $paths = [];

        if ($file instanceof File) {
            $paths[] = $file->getPathname();
        }

        if ($image instanceof Image) {
            foreach ($image->getVersions() as $imageVersion) {
                $paths[] = $this->photoDomainPath . $imageVersion->getUrl();
            }
        }

        foreach ($paths as $path) {
            try {
                if (is_file($path)) {
                    unlink($path);
                }
            } catch (Throwable $throwable) {
                $this->errorLogger->log($throwable);
            }
        }
    }

    protected function matchExifLabel(string $key): ?ImageExifLabel
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
