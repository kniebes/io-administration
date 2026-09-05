<?php declare(strict_types=1);

namespace App\Repository;

use App\Entity\BlogPost;
use App\Model\Filter\BlogPostIndexFilter;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;

class BlogPostRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $managerRegistry)
    {
        parent::__construct(registry: $managerRegistry, entityClass: BlogPost::class);
    }

    public function createFilterQuery(?BlogPostIndexFilter $filter = null): Query
    {
        $queryBuilder = $this->createQueryBuilder('p')
            ->orderBy(sort: 'p.created', order: 'DESC');

        $searchQuery = trim((string) $filter?->getSearchQuery());

        if ($searchQuery !== '') {
            $queryBuilder
                ->andWhere('p.searchableText LIKE :searchQuery')
                ->setParameter(key: 'searchQuery', value: '%' . $searchQuery . '%');
        }

        if ($filter?->getStatus() !== null) {
            $queryBuilder
                ->andWhere('p.status = :status')
                ->setParameter(key: 'status', value: $filter->getStatus());
        }

        if ($filter?->getBlog() !== null) {
            $queryBuilder
                ->andWhere('p.blog = :blog')
                ->setParameter(key: 'blog', value: $filter->getBlog());
        }

        if ($filter?->getBlogPostType() !== null) {
            $queryBuilder
                ->andWhere('p.blogPostType = :blogPostType')
                ->setParameter(key: 'blogPostType', value: $filter->getBlogPostType());
        }

        return $queryBuilder->getQuery();
    }
}
