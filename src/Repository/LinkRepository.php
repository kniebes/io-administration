<?php declare(strict_types=1);

namespace App\Repository;

use App\Entity\Link;
use App\Enum\LinkType;
use App\Model\Filter\LinkIndexFilter;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
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

    public function createFilterQuery(?LinkIndexFilter $filter = null): Query
    {
        $queryBuilder = $this->createQueryBuilder('link')
            ->where('link.type = :type')
            ->setParameter(key: 'type', value: LinkType::Bookmark)
            ->orderBy(sort: 'link.publishedDate', order: 'DESC');

        $searchQuery = trim((string) $filter?->getSearchQuery());
        if ($searchQuery !== '') {
            $searchableFields = ['link.title', 'link.url', 'link.alternateUrl', 'link.description', 'link.quote'];
            $queryBuilder
                ->andWhere($queryBuilder->expr()->orX(
                    ...array_map(
                        static fn (string $field): string => $field . ' LIKE :searchQuery',
                        $searchableFields
                    )
                ))
                ->setParameter(key: 'searchQuery', value: '%' . addcslashes($searchQuery, '%_\\') . '%');
        }

        return $queryBuilder->getQuery();
    }
}
