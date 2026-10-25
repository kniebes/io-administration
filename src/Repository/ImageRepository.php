<?php declare(strict_types=1);

namespace App\Repository;

use App\Entity\BlogPostImageMapping;
use App\Entity\ImageVersion;
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

    /**
     * @param list<int> $imageIds
     * @return array<int, int>
     */
    public function countBlogPostsByImageIds(array $imageIds): array
    {
        if ($imageIds === []) {
            return [];
        }

        $rows = $this->getEntityManager()->createQueryBuilder()
            ->select('IDENTITY(mapping.image) AS imageId', 'COUNT(mapping.id) AS blogPostCount')
            ->from(BlogPostImageMapping::class, 'mapping')
            ->where('mapping.image IN (:imageIds)')
            ->groupBy('mapping.image')
            ->setParameter(key: 'imageIds', value: $imageIds)
            ->getQuery()
            ->getArrayResult();

        $blogPostCounts = [];
        foreach ($rows as $row) {
            $blogPostCounts[(int) $row['imageId']] = (int) $row['blogPostCount'];
        }

        return $blogPostCounts;
    }

    public function createFilterQuery(?ImageIndexFilter $filter = null): Query
    {
        $queryBuilder = $this->createQueryBuilder('i')
            ->addSelect('v')
            ->leftJoin('i.versions', 'v')
            ->orderBy(sort: 'i.date', order: 'DESC');

        $searchQuery = trim((string) $filter?->getSearchQuery());
        if ($searchQuery !== '') {
            $searchableFields = ['i.title', 'i.description', 't.title', 't.description', 'e.value'];
            $queryBuilder
                ->leftJoin('i.translations', 't')
                ->leftJoin('i.exif', 'e')
                ->andWhere($queryBuilder->expr()->orX(
                    ...array_map(
                        static fn (string $field): string => $field . ' LIKE :searchQuery',
                        $searchableFields
                    )
                ))
                ->setParameter(key: 'searchQuery', value: '%' . $searchQuery . '%');
        }

        return $queryBuilder->getQuery();
    }
}
