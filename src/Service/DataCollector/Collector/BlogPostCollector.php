<?php declare(strict_types=1);

namespace App\Service\DataCollector\Collector;

use App\Model\DataCollector\BlogPostRequestData;
use App\Model\DataCollector\RequestDataInterface;
use App\Model\DataCollector\ResponseDataBag;
use App\Service\DataCollector\Collector\Interface\DataCollectorInterface;
use App\Repository\BlogPostRepository;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Serializer\SerializerInterface;

class BlogPostCollector implements DataCollectorInterface
{
    public function __construct(
        private readonly BlogPostRepository $blogPostRepository,
        private readonly SerializerInterface $serializer,
    )
    {
    }

    /**
     * @throws \Symfony\Component\Serializer\Exception\ExceptionInterface
     */
    public function collect(RequestDataInterface $requestData, ResponseDataBag $data): void
    {
        if ($requestData instanceof BlogPostRequestData) {
            if (!is_null($requestData->getId())) {
                $this->collectById(data: $data, id: $requestData->getId());
                return;
            }

            $this->collectBySlug(data: $data, requestData: $requestData);
        }
    }

    /**
     * @throws \Symfony\Component\Serializer\Exception\ExceptionInterface
     */
    protected function collectById(ResponseDataBag $data, int $id): void
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
