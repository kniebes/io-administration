<?php declare(strict_types=1);

namespace App\Repository;

use App\Entity\BlogPostSlugArchive;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class BlogPostSlugArchiveRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $managerRegistry)
    {
        parent::__construct(registry: $managerRegistry, entityClass: BlogPostSlugArchive::class);
    }

    public function save(BlogPostSlugArchive $slug): void
    {
        $entityManager = $this->getEntityManager();
        $entityManager->persist($slug);
        $entityManager->flush();
    }

    public function getTableName(): String
    {
        return $this->getEntityManager()->getClassMetadata(BlogPostSlugArchive::class)->getTableName();
    }

}
