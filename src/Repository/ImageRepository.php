<?php declare(strict_types=1);

namespace App\Repository;

use App\Model\Filter\ImageIndexFilter;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
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

    public function createFilterQuery(?ImageIndexFilter $filter = null): Query
    {
        $queryBuilder = $this->createQueryBuilder('i')
            ->orderBy(sort: 'i.date', order: 'DESC');

        $searchQuery = trim((string) $filter?->getSearchQuery());

        if ($searchQuery !== '') {
            $queryBuilder
                ->andWhere('p.searchableText LIKE :searchQuery')
                ->setParameter(key: 'searchQuery', value: '%' . $searchQuery . '%');
        }

        return $queryBuilder->getQuery();
    }
}
