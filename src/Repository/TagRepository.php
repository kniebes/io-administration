<?php declare(strict_types=1);

namespace App\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\Tag;

/**
 * @extends ServiceEntityRepository<Tag>
 */
class TagRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $managerRegistry)
    {
        parent::__construct(registry: $managerRegistry, entityClass: Tag::class);
    }

    /**
     * @return array<int, Tag>
     */
    public function findByTerm(string $term, int $limit = 10): array
    {
        return $this->createQueryBuilder('tag')
            ->where('tag.term LIKE :term')
            ->setParameter('term', '%' . $term . '%')
            ->orderBy('tag.term', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
