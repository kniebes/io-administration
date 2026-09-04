<?php declare(strict_types=1);

namespace App\Command\Migration;

use App\Entity\Image;
use App\Entity\ImageVersion;
use App\Enum\ImageLicense;
use App\Repository\ImageRepository;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Id\AssignedGenerator;
use Doctrine\ORM\Mapping\ClassMetadata;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:migration:migrate_images', description: 'Migrate Images from the current System')]
class ImageMigrationCommand
{
    public function __construct(
        private readonly Connection $migrationConnection,
        private readonly ImageRepository $imageRepository,
        private readonly EntityManagerInterface $entityManager,
    )
    {
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
            $imageMetrics = $this->getImageMetrics($importImage['id']);

            $customFields = json_decode(($importImage['custom_fields'] ?? ''), true);
            if (!is_array($customFields)) {
                $customFields = [];
            }

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

            $imageLicense = $importImage['image_license'] ?? '';
            $license = ImageLicense::tryFrom($imageLicense);
            if (is_null($license)) {
                $license = ImageLicense::PublicDomain;
            }

            $imageEntity = new Image();
            $metadata->setFieldValue($imageEntity, 'id', $importImage['id']);
            $imageEntity
                ->setTitle($importImage['title'] ?? '')
                ->setDescription($importImage['description'] ?? '')
                ->setUrl($importImage['url'] ?? '')
                ->setHost('https://'.($importImage['domain'] ?? ''))
                ->setMimeType($importImage['mime_type'] ?? '')
                ->setByteSize($importImage['file_size'] ?? 0)
                ->setExif($exif)
                ->setCustomFields($customFields)
                ->setDescription($importImage['content'] ?? '')
                ->setDescriptionEncoded($importImage['content_encoded'] ?? '')
                ->setLicense($license)
                ->setAltText($customFields['alt'] ?? '');
            $this->assignSize(imageEntity: $imageEntity, imageMetrics: $imageMetrics);
            $this->assignVersions(imageEntity: $imageEntity, importImage: $importImage, imageMetrics: $imageMetrics);
            $this->entityManager->persist($imageEntity);
            try {
                $this->entityManager->flush();
            } catch (\Throwable $e) {
                $io->error($e->getMessage());
                $io->info('ID: ' . $importImage['id']);
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
                ->setVersionIdentifier((string) $versionIdentifier);
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
            ->setAspectRatio($this->calcAspectRatio(width: $metrics['width'], height: $metrics['height']));
        ;
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
            ->setAspectRatio($this->calcAspectRatio(width: $metrics['width'], height: $metrics['height']));
            ;
    }

    private function calcAspectRatio(int $width, int $height): float
    {
        return round(($width / $height), 2);
    }
}
