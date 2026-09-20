<?php declare(strict_types=1);

namespace App\Service\DataCollector\Collector;

use App\Entity\Blog;
use App\Model\ContentApi\RequestData;
use App\Model\DataCollector\ResponseDataBag;
use App\Repository\BlogPostRepository;
use App\Service\DataCollector\Collector\Interface\DataCollectorInterface;
use DateTimeImmutable;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Component\Serializer\SerializerInterface;

class BlogPostsCollector implements DataCollectorInterface
{
    public function __construct(
        private BlogPostRepository $blogPostRepository,
        private SerializerInterface $serializer,
    ) {
    }

    public function collect(Blog $blog, string $method, RequestData $requestData, ResponseDataBag $data): void
    {
        if ($method !== DataCollectorInterface::METHOD_BLOG_POSTS) {
            return;
        }

        $status = $requestData->getQueryAsString('status');
        $page = $requestData->getQueryAsInt('page', 1);
        $perPage = $requestData->getQueryAsInt('per_page', 10);
        $isVisibleOnRss = $requestData->getQueryAsBool('is_visible_on_rss');
        $isVisibleOnWeb = $requestData->getQueryAsBool('is_visible_on_web');
        $tagSlug = $requestData->getQueryAsString('tag');
        $year = $requestData->getQueryAsInt('year');
        $month = $requestData->getQueryAsInt('month');

        $queryBuilder = $this->blogPostRepository
            ->createQueryBuilder('blogPost')
            ->addSelect('blogPost')
            ->addSelect('blogPostImages')
            ->addSelect('tags')
            ->addSelect('blogPostType')
            ->addSelect('categories')
            ->addSelect('links')
            ->leftJoin('blogPost.blogPostImages', 'blogPostImages')
            ->leftJoin('blogPost.blogPostType', 'blogPostType')
            ->leftJoin('blogPost.tags', 'tags')
            ->leftJoin('blogPost.categories', 'categories')
            ->leftJoin('blogPost.links', 'links')
        ;

        if (!is_null($status)) {
            $queryBuilder->andWhere('blogPost.status = :status')->setParameter('status', $status);
        }

        $queryBuilder->andWhere('blogPost.blog = :blog')->setParameter('blog', $blog);

        if (!is_null($isVisibleOnRss)) {
            $queryBuilder->andWhere('blogPost.isVisibleOnRss = :is_visible_on_rss' )->setParameter('is_visible_on_rss', $isVisibleOnRss);
        }

        if (!is_null($isVisibleOnWeb)) {
            $queryBuilder->andWhere('blogPost.isVisibleOnWeb = :is_visible_on_web' )->setParameter('is_visible_on_web', $isVisibleOnWeb);
        }

        if (!is_null($year)) {
            $startOfPeriod = new DateTimeImmutable()
                ->setDate(year: (int) $year, month: is_null($month) ? 1 : (int) $month, day: 1)
                ->setTime(hour: 0, minute: 0);
            $startOfNextPeriod = $startOfPeriod->modify(is_null($month) ? '+1 year' : '+1 month');

            $queryBuilder
                ->andWhere('blogPost.publishedDate >= :startOfPeriod')
                ->andWhere('blogPost.publishedDate < :startOfNextPeriod')
                ->setParameter(key: 'startOfPeriod', value: $startOfPeriod)
                ->setParameter(key: 'startOfNextPeriod', value: $startOfNextPeriod);
        }

        if (!is_null($tagSlug)) {
            $queryBuilder
                ->innerJoin('blogPostp.tags', 'filterTag', 'WITH', 'filterTag.slug = :tagSlug')
                ->setParameter(key: 'tagSlug', value: $tagSlug);
        }

        $queryBuilder
            ->addOrderBy('blogPost.publishedDate', 'DESC')
            ->addOrderBy('blogPost.id', 'DESC')
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage);

        $paginator = new Paginator(query: $queryBuilder->getQuery(), fetchJoinCollection: true);

        $serialisedData = $this->serializer->serialize(
            data: iterator_to_array($paginator),
            format: 'json',
            context: [
                'groups' => ['blog_post:read'],
                'config' => $requestData->getConfig(),
            ]
        );
        $data->setData(key: 'list', data: json_decode($serialisedData, true));
        $data->setData(key: 'total', data: count($paginator));
    }

}
