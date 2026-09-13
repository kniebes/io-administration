<?php declare(strict_types=1);

namespace App\Service\DataCollector\Collector;

use App\Entity\Blog;
use App\Model\DataCollector\BlogPostRequestData;
use App\Model\DataCollector\RequestDataInterface;
use App\Model\DataCollector\ResponseDataBag;
use App\Service\DataCollector\Collector\Interface\DataCollectorInterface;
use App\Repository\BlogPostRepository;
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
        if ($method !== 'blog-post') {
            return;
        }

        $id = $request->request->get('id', null);
        if (!is_null($id)) {
            $this->collectById(data: $data, id: $id, blog: $blog);
        }
    }

    /**
     * @throws \Symfony\Component\Serializer\Exception\ExceptionInterface
     */
    protected function collectById(ResponseDataBag $data, int $id, Blog $blog): void
    {
        $blogPost = $this->blogPostRepository->find($id);
        if (is_null($blogPost)) {
            throw new NotFoundHttpException('Blog post not found');
        }

        $serializedBlogPost = $this->serializer->serialize($blogPost, 'json', ['groups' => ['blog_post:read']]);
        $data->setData('blogPost', json_decode($serializedBlogPost, true));
    }

    /**
     * @throws \Symfony\Component\Serializer\Exception\ExceptionInterface
     */
    protected function collectBySlug(ResponseDataBag $data, BlogPostRequestData $requestData): void
    {
        $blogPost = $this->blogPostRepository->findBy(['slug' => $requestData->getSlug()]);
        if (is_null($blogPost)) {
            throw new NotFoundHttpException('Blog post not found');
        }

        $serializedBlogPost = $this->serializer->serialize($blogPost, 'json', ['groups' => ['blog_post:read']]);
        $data->setData('blogPost', json_decode($serializedBlogPost, true));
    }
}
