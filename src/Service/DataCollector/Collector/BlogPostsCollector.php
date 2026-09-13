<?php declare(strict_types=1);

namespace App\Service\DataCollector\Collector;

use App\Entity\Blog;
use App\Model\DataCollector\ResponseDataBag;
use App\Repository\BlogPostRepository;
use App\Service\DataCollector\Collector\Interface\DataCollectorInterface;
use DateTimeImmutable;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\SerializerInterface;

readonly class BlogPostsCollector implements DataCollectorInterface
{
    public function __construct(
        private BlogPostRepository $blogPostRepository,
        private SerializerInterface $serializer,
    ) {
    }

    public function collect(Blog $blog, string $method, Request $request, ResponseDataBag $data): void
    {
        if ($method !== DataCollectorInterface::METHOD_BLOG_POSTS) {
            return;
        }

        $status = $request->request->get('status', null);
        $page = intval($request->request->get('page', 1));
        $perPage = intval($request->request->get('per_page', 10));
        $isVisibleOnRss = $request->request->get('is_visible_on_rss', null);
        $isVisibleOnWeb = $request->request->get('is_visible_on_web', null);
        $tagSlug = $request->request->get('tag', null);
        $year = $request->request->get('year', null);
        $month = $request->request->get('month', null);

        $queryBuilder = $this->blogPostRepository
            ->createQueryBuilder('p')
            ->addSelect('p')
            ->addSelect('i')
            ->addSelect('t')
            ->addSelect('type')
            ->addSelect('c')
            ->leftJoin('p.blogPostImages', 'i')
            ->leftJoin('p.blogPostType', 'type')
            ->leftJoin('p.tags', 't')
            ->leftJoin('p.categories', 'c');

        if (!is_null($status)) {
            $queryBuilder->andWhere('p.status = :status')->setParameter('status', $status);
        }

        $queryBuilder->andWhere('p.blog = :blog')->setParameter('blog', $blog);

        if (!is_null($isVisibleOnRss)) {
            $queryBuilder->andWhere('p.isVisibleOnRss = :is_visible_on_rss' )->setParameter('is_visible_on_rss', $isVisibleOnRss);
        }

        if (!is_null($isVisibleOnWeb)) {
            $queryBuilder->andWhere('p.isVisibleOnWeb = :is_visible_on_web' )->setParameter('is_visible_on_web', $isVisibleOnWeb);
        }

        if (!is_null($year)) {
            $startOfPeriod = new DateTimeImmutable()
                ->setDate(year: (int) $year, month: is_null($month) ? 1 : (int) $month, day: 1)
                ->setTime(hour: 0, minute: 0);
            $startOfNextPeriod = $startOfPeriod->modify(is_null($month) ? '+1 year' : '+1 month');

            $queryBuilder
                ->andWhere('p.publishedDate >= :startOfPeriod')
                ->andWhere('p.publishedDate < :startOfNextPeriod')
                ->setParameter(key: 'startOfPeriod', value: $startOfPeriod)
                ->setParameter(key: 'startOfNextPeriod', value: $startOfNextPeriod);
        }

        if (!is_null($tagSlug)) {
            $queryBuilder
                ->innerJoin('p.tags', 'filterTag', 'WITH', 'filterTag.slug = :tagSlug')
                ->setParameter(key: 'tagSlug', value: $tagSlug);
        }

        $countQuery = clone $queryBuilder;
        $total = $countQuery
            ->select('COUNT(DISTINCT p.id)')
            ->resetDQLPart('orderBy')
            ->setFirstResult(0)
            ->setMaxResults(null)
            ->getQuery()
            ->getSingleScalarResult();

        $queryBuilder->setMaxResults(intval($perPage));
        $queryBuilder->setFirstResult(($page - 1) * $perPage);
        $queryBuilder->addOrderBy('p.publishedDate', 'DESC');

        $maxMaxResults = $queryBuilder->getQuery()->getMaxResults();

        $result = $queryBuilder->getQuery()->getResult();
        $serialisedData = $this->serializer->serialize($result, 'json', ['groups' => ['blog_post:read']]);
        $data->setData(key: 'list', data: json_decode($serialisedData, true));
        $data->setData(key: 'total', data: $total);
    }

}
