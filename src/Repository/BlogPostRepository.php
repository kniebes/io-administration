<?php declare(strict_types=1);

namespace App\Repository;

use App\Entity\BlogPost;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;

class BlogPostRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $managerRegistry)
    {
        parent::__construct(registry: $managerRegistry, entityClass: BlogPost::class);
    }

    public function createSearchQuery():Query
    {
        $dql   = 'SELECT p FROM App\Entity\BlogPost p ORDER BY p.created DESC';

        return $this->getEntityManager()->createQuery($dql);
    }
}
