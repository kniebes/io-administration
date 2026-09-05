<?php declare(strict_types=1);

namespace App\Command\Migration;

use App\Entity\Category;
use App\Entity\Image;
use App\Entity\ImageExif;
use App\Entity\ImageTranslation;
use App\Entity\ImageVersion;
use App\Enum\ImageExifLabel;
use App\Enum\ImageLicense;
use App\Repository\CategoryRepository;
use App\Repository\ImageRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Id\AssignedGenerator;
use Doctrine\ORM\Mapping\ClassMetadata;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

#[AsCommand(name: 'app:migration:migrate_images', description: 'Migrate Images from the current System')]
readonly class ImageMigrationCommand
{
    public function __construct(
        private Connection $migrationConnection,
        private ImageRepository $imageRepository,
        private CategoryRepository $categoryRepository,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function __invoke(SymfonyStyle $io): int
    {
        $sql = 'SELECT * FROM journal_image ORDER BY id DESC';
        $images = $this->migrationConnection->fetchAllAssociative($sql);

        $metadata = $this->entityManager->getClassMetadata(Image::class);
        $metadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_NONE);
        $metadata->setIdGenerator(new AssignedGenerator());

        $io->progressStart(count($images));
        foreach ($images as $importImage) {
            if ($this->isImageEntityExists($importImage['id'])) {
                $io->progressAdvance();
                continue;
            }

            // Metrics
            $imageMetrics = $this->getImageMetrics($importImage['id']);

            // customFields
            $customFields = json_decode(($importImage['custom_fields'] ?? ''), true);
            if (!is_array($customFields)) {
                $customFields = [];
            }

            // Exif
            $exif = json_decode(($importImage['exif'] ?? ''), true);
            if (!is_array($exif)) {
                $exif = [];
            }
            foreach (['altitude', 'longitude', 'latitude', 'googlemap', 'openStreetMap'] as $field) {
                if (isset($exif[$field])) {
                    if (!empty($exif[$field])) {
                        $customFields[$field] = $exif[$field];
                    }
                    unset($exif[$field]);
                }
            }

            // Flickr
            if (!empty($importImage['flickr_info'])) {
                $flickrInfo = json_decode($importImage['flickr_info'], true);
                if (is_array($flickrInfo)) {
                    $customFields['flickr_info'] = $flickrInfo;
                }
            }
            $customFields['flickr_status'] = $importImage['flickr_status'];

            // License
            $imageLicense = $importImage['image_license'] ?? '';
            $license = ImageLicense::tryFrom($imageLicense);
            if (is_null($license)) {
                $license = ImageLicense::PublicDomain;
            }

            // Dates
            try {
                $date = new DateTimeImmutable($importImage['date'] ?? null);
            } catch (\DateMalformedStringException $e) {
                $io->warning('Invalid date: '.$e->getMessage());
                $date = new DateTimeImmutable();
            }
            try {
                $created = new DateTimeImmutable($importImage['created'] ?? null);
            } catch (\DateMalformedStringException $e) {
                $io->warning('Invalid created: '.$e->getMessage());
                $created = new DateTimeImmutable();
            }
            try {
                $updated = new DateTimeImmutable($importImage['changed'] ?? null);
            } catch (\DateMalformedStringException $e) {
                $io->warning('Invalid updated: '.$e->getMessage());
                $updated = new DateTimeImmutable();
            }

            // Create Image Entity
            $imageEntity = new Image();
            $metadata->setFieldValue($imageEntity, 'id', $importImage['id']);
            $imageEntity
                ->setDate($date)
                ->setCreated($created)
                ->setUpdated($updated)
                ->setTitle($importImage['title'] ?? '')
                ->setDescription($importImage['description'] ?? '')
                ->setUrl($importImage['url'] ?? '')
                ->setHost('https://'.($importImage['domain'] ?? ''))
                ->setMimeType($importImage['mime_type'] ?? '')
                ->setByteSize($importImage['file_size'] ?? 0)
                ->setCustomFields($customFields)
                ->setDescription($importImage['content'] ?? '')
                ->setDescriptionEncoded($importImage['content_encoded'] ?? '')
                ->setLicense($license)
                ->setAltText($customFields['alt'] ?? '');

            $this->assignSize(imageEntity: $imageEntity, imageMetrics: $imageMetrics);
            $this->assignVersions(imageEntity: $imageEntity, importImage: $importImage, imageMetrics: $imageMetrics);
            $this->assignTranslation(imageEntity: $imageEntity, importImage: $importImage, customFields: $customFields);
            $this->assignCategory(imageEntity: $imageEntity, importImage: $importImage);
            $this->assignExif(imageEntity: $imageEntity, importImage: $importImage);

            $this->entityManager->persist($imageEntity);
            try {
                $this->entityManager->flush();
            } catch (Throwable $e) {
                $io->error($e->getMessage());
                $io->info('ID: '.$importImage['id']);
            }
            $this->entityManager->clear();
            $io->progressAdvance();
        }
        $io->progressFinish();

        return Command::SUCCESS;
    }

    private function getImageMetrics(int $imageId): array
    {
        $url = 'https://kniebes.com/api/image-metric?imageId='.$imageId;
        $json = file_get_contents($url);
        $data = json_decode($json, true);
        if (!is_array($data)) {
            return [];
        }

        return $data;
    }

    private function assignVersions(Image $imageEntity, array $importImage, array $imageMetrics): void
    {
        $versions = $importImage['versions'] ?? null;
        if (is_null($versions)) {
            return;
        }

        $versionList = json_decode($versions, true);
        if (!is_array($versionList)) {
            return;
        }

        foreach ($versionList as $versionIdentifier => $versionUrl) {
            $imageVersion = new ImageVersion();
            $imageVersion->setUrl($versionUrl)
                ->setVersionIdentifier((string)$versionIdentifier);
            $this->assignVersionSizes(imageVersion: $imageVersion, imageMetrics: $imageMetrics);
            $imageEntity->addImageVersion($imageVersion);
        }
    }

    private function assignVersionSizes(ImageVersion $imageVersion, array $imageMetrics): void
    {
        $metrics = $imageMetrics[$imageVersion->getVersionIdentifier()] ?? null;
        if (is_null($metrics)) {
            $imageVersion
                ->setWidth(0)
                ->setHeight(0)
                ->setByteSize(0);

            return;
        }

        $imageVersion
            ->setWidth($metrics['width'] ?? 0)
            ->setHeight($metrics['height'] ?? 0)
            ->setByteSize($metrics['filesize'] ?? 0)
            ->setAspectRatio($this->calcAspectRatio(width: $metrics['width'], height: $metrics['height']));;
    }

    private function isImageEntityExists(int $id): bool
    {
        $imageEntity = $this->imageRepository->find($id);

        return null !== $imageEntity;
    }

    private function assignSize(Image $imageEntity, array $imageMetrics): void
    {
        $metrics = $imageMetrics['original'] ?? null;
        if (is_null($metrics)) {
            $imageEntity
                ->setWidth(0)
                ->setHeight(0)
                ->setByteSize(0);

            return;
        }

        $imageEntity
            ->setWidth($metrics['width'] ?? 0)
            ->setHeight($metrics['height'] ?? 0)
            ->setByteSize($metrics['filesize'] ?? 0)
            ->setMimeType($metrics['mimeType'] ?? '')
            ->setAspectRatio($this->calcAspectRatio(width: $metrics['width'], height: $metrics['height']));;
    }

    private function calcAspectRatio(int $width, int $height): float
    {
        return round(($width / $height), 2);
    }

    private function assignTranslation(Image $imageEntity, array $importImage, array $customFields): void
    {
        if (!empty($importImage['content_en'])
            || !empty($importImage['content_encoded_en'])
            || !empty($importImage['title_en'])) {
            $translation = new ImageTranslation();
            $translation
                ->setDescription($importImage['content_en'] ?? '')
                ->setDescriptionEncoded($importImage['content_encoded_en'] ?? '')
                ->setTitle($importImage['title_en'] ?? '')
                ->setAltText($customFields['alt'] ?? '');
            $imageEntity->addTranslation($translation);
        }
    }

    private function assignCategory(Image $imageEntity, array $importImage): void
    {
        $slug = match($importImage['category']) {
            'landscape' => 'landschaft',
            'cemetery' => 'friedhof',
            'unsorted' => 'unsortiert',
            default => $importImage['category'],
        };

        $category = $this->categoryRepository->findOneBy(['slug' => $slug]);
        if (is_null($category)) {
            return;
        }

        $imageEntity->addCategory($category);
    }

    /*
 {
    "title": "",
    "description": "",
    "copyright": "Markus kniebes",
    "model": "Nikon Z 8",
    "lens": "NIKKOR Z MC 105mm f\/2.8 VR S",
    "focallength": "105mm",
    "aperture": "f\/3.2",
    "exposure": "1\/250s",
    "iso": "ISO 64",
    "time": "04.09.2026 12:00",
    "longitude": "",
    "latitude": "",
    "altitude": "",
    "googlemap": "",
    "openStreetMap": ""
}
     */
    private function assignExif(Image $imageEntity, array $importImage): void
    {
        if (empty($importImage['exif'])) {
            return;
        }

        $data = json_decode($importImage['exif'], true);
        if (!is_array($data)) {
            return;
        }

        foreach ($data as $label => $value) {
            $imageExif = new ImageExif();
            $labelEnum = $this->matchExifLabelToEnum($label);
            if (is_null($labelEnum)) {
                continue;
            }

            $imageExif
                ->setLabel($labelEnum)
                ->setValue((string) $value);
            $imageEntity->addExif($imageExif);
        }
    }

    private function matchExifLabelToEnum(string $label): ?ImageExifLabel
    {
        return match ($label) {
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
