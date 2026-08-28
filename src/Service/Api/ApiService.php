<?php declare(strict_types=1);

namespace App\Service\Api;

use App\Model\DataCollector\BlogPostRequestData;
use App\Service\DataCollector\Interface\DataCollectorServiceInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Translation\Exception\NotFoundResourceException;

readonly class ApiService
{
    public function __construct(
        private DataCollectorServiceInterface $dataCollectorService,
    ) {
    }

    public function collectData(string $method, Request $request): array
    {
        return match ($method) {
            'blog-post' => $this->getBlogPost($request),
            default => throw new NotFoundResourceException()
        };
    }

    protected function getBlogPost(Request $request): array
    {
        $requestData = $this->createBlogPostRequestData($request);

        $result = $this->dataCollectorService->collect($requestData);
        if (empty($result->getData())) {
            throw new NotFoundResourceException();
        }

        return $result->getData();
    }

    protected function createBlogPostRequestData(Request $request): BlogPostRequestData
    {
        return new BlogPostRequestData(
            id: (int) $request->query->get('id'),
            year: (int) $request->query->get('year'),
            month: (int) $request->query->get('month'),
            day: (int) $request->query->get('day'),
            slug: $request->query->get('slug'),
        );
    }
}
