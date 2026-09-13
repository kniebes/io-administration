<?php declare(strict_types=1);

namespace App\Service\DataCollector\Collector;

use App\Entity\Blog;
use App\Entity\BlogPost;
use App\Enum\BlogPostStatus;
use App\Model\DataCollector\ResponseDataBag;
use App\Repository\BlogPostRepository;
use App\Service\BlogPost\PermaLinkFactory;
use App\Service\DataCollector\Collector\Interface\DataCollectorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\SerializerInterface;

readonly class BlogpostMetaCollector implements DataCollectorInterface
{
    public function __construct(
        private BlogPostRepository $blogPostRepository,
        private SerializerInterface $serializer,
        private PermaLinkFactory $permaLinkFactory,
    ) {
    }

    public function collect(Blog $blog, string $method, Request $request, ResponseDataBag $data): void
    {
        if ($method !== DataCollectorInterface::METHOD_BLOG_POST_META) {
            return;
        }

        $id = $request->request->get('id', null);
        if (is_null($id)) {
            return;
        }

        $blogPost = $this->blogPostRepository->find($id);
        $this->assignPreviousPostUrl(blog: $blog, result: $blogPost, data: $data);
        $this->assignNextPostUrl(blog: $blog, result: $blogPost, data: $data);
    }

    protected function assignPreviousPostUrl(Blog $blog, BlogPost $result, ResponseDataBag $data): void
    {
        $queryBuilder = $this->blogPostRepository->createQueryBuilder('p');
        $result = $queryBuilder
            ->select('p.id', 'p.slug', 'p.publishedDate', 'p.title')
            ->where('p.blog = :blog')->setParameter('blog', $blog)
            ->andWhere('p.status = :status')->setParameter('status', BlogPostStatus::Published)
            ->andWhere('p.publishedDate < :now')->setParameter('now', $result->getPublishedDate())
            ->orderBy('p.publishedDate', 'DESC')
            ->setMaxResults(1)
            ->getQuery()->getOneOrNullResult();

        if (empty($result)) {
            $data->setData(key: 'previous_post', data: null);
        }

        $data->setData(key:'previous_post', data: [
            'url' => $this->permaLinkFactory->create(blog: $blog, blogPostData: $result),
            'title' => $result['title']
        ]);
    }

    protected function assignNextPostUrl(Blog $blog, BlogPost $result, ResponseDataBag $data): void
    {
        $queryBuilder = $this->blogPostRepository->createQueryBuilder('p');
        $result = $queryBuilder
            ->select('p.id', 'p.slug', 'p.publishedDate', 'p.title')
            ->where('p.blog = :blog')->setParameter('blog', $blog)
            ->andWhere('p.status = :status')->setParameter('status', BlogPostStatus::Published)
            ->andWhere('p.publishedDate > :now')->setParameter('now', $result->getPublishedDate())
            ->orderBy('p.publishedDate', 'ASC')
            ->setMaxResults(1)
            ->getQuery()->getOneOrNullResult();

        if (empty($result)) {
            $data->setData(key: 'next_post', data: null);
        }

        $data->setData(key: 'next_post', data: [
            'url' => $this->permaLinkFactory->create(blog: $blog, blogPostData: $result),
            'title' => $result['title'],
        ]);
    }
}
