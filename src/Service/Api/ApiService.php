<?php declare(strict_types=1);

namespace App\Service\Api;

use App\Entity\Blog;
use App\Model\DataCollector\BlogPostRequestData;
use App\Model\DataCollector\BlogPostsRequestData;
use App\Model\DataCollector\ResponseDataBag;
use App\Service\DataCollector\Interface\DataCollectorServiceInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Translation\Exception\NotFoundResourceException;

readonly class ApiService
{
    public function __construct(
        private DataCollectorServiceInterface $dataCollectorService,
    ) {
    }

    public function collectData(Blog $blog, string $method, Request $request): ResponseDataBag
    {
        return  $this->dataCollectorService->collect(blog: $blog, method: $method, request: $request);
    }

}
