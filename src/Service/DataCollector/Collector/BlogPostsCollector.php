<?php declare(strict_types=1);

namespace App\Service\DataCollector\Collector;

use App\Entity\Blog;
use App\Model\DataCollector\ResponseDataBag;
use App\Repository\BlogPostRepository;
use App\Service\DataCollector\Collector\Interface\DataCollectorInterface;
use DateTimeImmutable;
use Doctrine\ORM\Tools\Pagination\Paginator;
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
            ->createQueryBuilder('blogPost')
            ->addSelect('blogPost')
            ->addSelect('blogPostImages')
            ->addSelect('tags')
            ->addSelect('blogPostType')
            ->addSelect('categories')
            ->leftJoin('blogPost.blogPostImages', 'blogPostImages')
            ->leftJoin('blogPost.blogPostType', 'blogPostType')
            ->leftJoin('blogPost.tags', 'tags')
            ->leftJoin('blogPost.categories', 'categories');

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

        $serialisedData = $this->serializer->serialize(iterator_to_array($paginator), 'json', ['groups' => ['blog_post:read']]);
        $data->setData(key: 'list', data: json_decode($serialisedData, true));
        $data->setData(key: 'total', data: count($paginator));
    }

}
