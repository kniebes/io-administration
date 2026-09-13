<?php declare(strict_types=1);

namespace App\Service\DataCollector\Collector;

use App\Entity\Blog;
use App\Entity\BlogPost;
use App\Enum\BlogPostStatus;
use App\Model\DataCollector\BlogPostRequestData;
use App\Model\DataCollector\RequestDataInterface;
use App\Model\DataCollector\ResponseDataBag;
use App\Service\DataCollector\Collector\Interface\DataCollectorInterface;
use App\Repository\BlogPostRepository;
use DateTimeImmutable;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpClient\Exception\InvalidArgumentException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Serializer\SerializerInterface;

readonly class BlogPostCollector implements DataCollectorInterface
{
    public function __construct(
        private BlogPostRepository $blogPostRepository,
        private SerializerInterface $serializer,
    ) {
    }

    /**
     * @throws \Symfony\Component\Serializer\Exception\ExceptionInterface
     */
    public function collect(Blog $blog, string $method, Request $request, ResponseDataBag $data): void
    {
        if ($method !== DataCollectorInterface::METHOD_BLOG_POST) {
            return;
        }

        $id = $request->request->get('id', null);
        $year = $request->request->get('year', null);
        $year = !is_null($year) ? (int) $year : null;
        $month = $request->request->get('month', null);
        $month = !is_null($month) ? (int) $month : null;
        $day = $request->request->get('day', null);
        $day = !is_null($day) ? (int) $day : null;
        $slug = $request->request->get('slug', null);
        $statusText = $request->request->get('status', 'published');
        $status = BlogPostStatus::tryFrom($statusText);
        if (is_null($status)) {
            $status = BlogPostStatus::Published;
        }

        $queryBuilder = !is_null($id)
            ? $this->createQueryBuilderForId(data: $data, id: (int)$id, blog: $blog)
            : $this->createQueryBuilderForSlug(data: $data, blog: $blog, year: $year, month: $month, day: $day, slug: $slug);
        $queryBuilder->andWhere('blogPost.status = :status')->setParameter('status', $status);
        $blogPost = $queryBuilder->getQuery()->getOneOrNullResult();

        if (is_null($blogPost)) {
            throw new NotFoundHttpException('Blog post not found');
        }

        $serializedBlogPost = $this->serializer->serialize($blogPost, 'json', ['groups' => ['blog_post:read']]);
        $data->setData('blog_post', json_decode($serializedBlogPost, true));

    }

    /**
     * @throws \Symfony\Component\Serializer\Exception\ExceptionInterface
     */
    protected function createQueryBuilderForId(ResponseDataBag $data, int $id, Blog $blog): QueryBuilder
    {
        return $this->blogPostRepository
            ->createQueryBuilder('blogPost')
            ->addSelect('blogPost')
            ->addSelect('blogPostImages')
            ->addSelect('tags')
            ->addSelect('blogPostType')
            ->addSelect('categories')
            ->leftJoin('blogPost.blogPostImages', 'blogPostImages')
            ->leftJoin('blogPost.blogPostType', 'blogPostType')
            ->leftJoin('blogPost.tags', 'tags')
            ->leftJoin('blogPost.categories', 'categories')
            ->where('blogPost.id = :id')
            ->andWhere('blogPost.blog = :blog')
            ->andWhere('blogPost.status = :status')
            ->setParameter(key: 'id', value: $id)
            ->setParameter(key: 'blog', value: $blog)
            ->setParameter(key: 'status', value: BlogPostStatus::Published);
    }


    /**
     * @throws \Symfony\Component\Serializer\Exception\ExceptionInterface
     */
    protected function createQueryBuilderForSlug(
        ResponseDataBag $data,
        Blog $blog,
        ?int $year = null,
        ?int $month = null,
        ?int $day = null,
        ?string $slug = null
    ): QueryBuilder {
        if (is_null($slug) || is_null($year) || is_null($month) || is_null($day)) {
            throw new InvalidArgumentException('invalid Arguments');
        }

        if (!checkdate($month, $day, $year)) {
            throw new InvalidArgumentException('no proper date given');
        }

        $startOfDay = new DateTimeImmutable()
            ->setDate(year: $year, month: $month, day: $day)
            ->setTime(hour: 0, minute: 0);
        $startOfNextDay = $startOfDay->modify('+1 day');

        return $this->blogPostRepository
            ->createQueryBuilder('blogPost')
            ->addSelect('blogPost')
            ->addSelect('blogPostImages')
            ->addSelect('tags')
            ->addSelect('blogPostType')
            ->addSelect('categories')
            ->leftJoin('blogPost.blogPostImages', 'blogPostImages')
            ->leftJoin('blogPost.blogPostType', 'blogPostType')
            ->leftJoin('blogPost.tags', 'tags')
            ->leftJoin('blogPost.categories', 'categories')
            ->where('blogPost.slug = :slug')
            ->andWhere('blogPost.blog = :blog')
            ->andWhere('blogPost.publishedDate >= :startOfDay')
            ->andWhere('blogPost.publishedDate < :startOfNextDay')
            ->andWhere('blogPost.status = :status')
            ->setParameter(key: 'slug', value: $slug)
            ->setParameter(key: 'blog', value: $blog)
            ->setParameter(key: 'startOfDay', value: $startOfDay)
            ->setParameter(key: 'startOfNextDay', value: $startOfNextDay)
            ->setParameter(key: 'status', value: BlogPostStatus::Published);
    }
}
