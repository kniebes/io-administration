<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\Api\ApiService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

class ApiController extends AbstractController
{
    public function __construct(
        private readonly APiService $apiService,
        private readonly LoggerInterface $logger,
    )
    {
    }

    #[Route(
        path:'/content-api/{method}',
        name: 'content_api',
        requirements: ['method' => '[a-z\-]+'],
        methods: ['POST'],
    )]
    public function index(string $method, Request $request): Response
    {
        try {
            $response = $this->apiService->collectData(method: $method, request: $request);
            $data = $response->getData();
            $data['success'] = !$response->hasErrors();
            $data['errors'] = $response->getErrors();

        } catch (Throwable $throwable) {
            $data = ['success' => false];
            $this->logger->critical(message: $throwable->getMessage(), context: [
                'method' => $method,
                'request' => $request->request->all(),
                'file' => $throwable->getFile(),
                'line' => $throwable->getLine(),
            ]);
        }

        return new JsonResponse($data);
    }
}
