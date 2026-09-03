<?php declare(strict_types=1);

namespace App\Command\Migration;

use App\Entity\Image;
use App\Entity\ImageVersion;
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
        $sql = 'SELECT * FROM journal_image ORDER BY id DESC LIMIT 5';
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
            $imageEntity = new Image();
            $metadata->setFieldValue($imageEntity, 'id', $importImage['id']);
            $imageEntity
                ->setTitle($importImage['title'] ?? '')
                ->setDescription($importImage['description'] ?? '')
                ->setUrl($importImage['url'] ?? '')
                ->setHost('https://'.($importImage['domain'] ?? ''))
                ->setMimeType($importImage['mime_type'] ?? '')
                ->setByteSize($importImage['file_size'] ?? 0)
                ->setAltText('');
            $this->assignSize(imageEntity: $imageEntity, importImage: $importImage);
            $this->assignVersions(imageEntity: $imageEntity, importImage: $importImage);
            $this->entityManager->persist($imageEntity);
            $io->progressAdvance();
        }
        $io->progressFinish();

        $this->entityManager->flush();

        return Command::SUCCESS;
    }

    private function assignVersions(Image $imageEntity, array $importImage): void
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
            $this->assignVersionSizes(imageVersion: $imageVersion, versionUrl: $versionUrl);
            $imageEntity->addImageVersion($imageVersion);
        }
    }

    private function assignVersionSizes(ImageVersion $imageVersion, string $versionUrl): void
    {
        // @TODO ich muss den regex im alten Projekt suchen oder auf die Bilder im Dateisystem zugreifen
        $imageVersion->setWidth(0)
            ->setHeight(0)
            ->setByteSize(0);
    }

    private function isImageEntityExists(int $id): bool
    {
        $imageEntity = $this->imageRepository->find($id);

        return null !== $imageEntity;
    }

    private function assignSize(Image $imageEntity, array $importImage): void
    {
        // @TODO Ich habe keine andere Wahl als das über das Dateisystem zu machen
        $imageEntity->setWidth(1)
            ->setHeight(1);
    }
}
