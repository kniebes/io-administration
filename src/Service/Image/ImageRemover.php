<?php declare(strict_types=1);

namespace App\Service\Image;

use App\Entity\BlogPostImageMapping;
use App\Entity\Image;
use Doctrine\ORM\EntityManagerInterface;

readonly class ImageRemover
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ImageFileRemover $imageFileRemover,
    ) {
    }

    public function remove(Image $image): void
    {
        $paths = $this->imageFileRemover->collectPaths($image);

        $this->entityManager->wrapInTransaction(function () use ($image): void {
            $this->entityManager->createQueryBuilder()
                ->delete(BlogPostImageMapping::class, 'mapping')
                ->where('mapping.image = :image')
                ->setParameter('image', $image)
                ->getQuery()
                ->execute();

            $this->entityManager->remove($image);
        });

        $this->imageFileRemover->removePaths($paths);
    }
}
