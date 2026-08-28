<?php declare(strict_types=1);

namespace App\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\Image;

/**
 * @extends ServiceEntityRepository<Image>
 */
class ImageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $managerRegistry)
    {
        parent::__construct(registry: $managerRegistry, entityClass: Image::class);
    }

    public function findOneByUrl(string $url): ?Image
    {
        return $this->findOneBy(['url' => $url]);
    }

    public function save(Image $image): void
    {
        $entityManager = $this->getEntityManager();
        $entityManager->persist($image);
        $entityManager->flush();
    }
}
