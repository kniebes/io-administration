<?php declare(strict_types=1);

namespace App\Service\DataCollector\Collector;

use App\Entity\Blog;
use App\Model\DataCollector\ResponseDataBag;
use App\Repository\BlogPostRepository;
use App\Service\DataCollector\Collector\Interface\DataCollectorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\SerializerInterface;

class BlogPostsCollector implements DataCollectorInterface
{
    public function __construct(
        private BlogPostRepository $blogPostRepository,
        private SerializerInterface $serializer,
    ) {
    }

    public function collect(Blog $blog, string $method, Request $request, ResponseDataBag $data): void
    {
        if ($method !== 'blog-posts') {
            return;
        }

        $status = $request->request->get('status', null);
        $page = intval($request->request->get('page', 1));
        $perPage = intval($request->request->get('per_page', 10));
        $isVisibleOnRss = $request->request->get('is_visible_on_rss', null);
        $isVisibleOnWeb = $request->request->get('is_visible_on_web', null);
        $tag = $request->request->get('tag', null);
        $year = $request->request->get('year', null);
        $month = $request->request->get('month', null);

        $query = $this->blogPostRepository->createQueryBuilder('p');
        if (!is_null($status)) {
            $query->andWhere('p.status = :status')->setParameter('status', $status);
        }

        $query->andWhere('p.blog = :blog')->setParameter('blog', $blog);

        $countQuery = clone $query;
        $total = $countQuery
            ->select('COUNT(DISTINCT p.id)')
            ->resetDQLPart('orderBy')
            ->setFirstResult(0)
            ->setMaxResults(null)
            ->getQuery()
            ->getSingleScalarResult();

        $query->setMaxResults(intval($perPage));
        $query->setFirstResult(($page - 1) * $perPage);
        $query->addOrderBy('p.publishedDate', 'DESC');

        $maxMaxResults = $query->getQuery()->getMaxResults();

        $result = $query->getQuery()->getResult();
        $serialisedData = $this->serializer->serialize($result, 'json', ['groups' => ['blog_post:read']]);
        $data->setData(key: 'list', data: json_decode($serialisedData, true));
        $data->setData(key: 'total', data: $total);
    }

}
