<?php declare(strict_types=1);

namespace App\Repository;

use App\Entity\Link;
use App\Enum\LinkType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Link>
 */
class LinkRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $managerRegistry)
    {
        parent::__construct(registry: $managerRegistry, entityClass: Link::class);
    }

    public function findOrphaned(): array
    {
        return $this->createQueryBuilder('link')
            ->where('link.type = :type')
            ->andWhere('SIZE(link.blogPosts) = 0')
            ->setParameter('type', LinkType::BlogPostLink)
            ->getQuery()
            ->getResult();
    }
}
