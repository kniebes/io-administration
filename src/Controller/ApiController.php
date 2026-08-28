<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\Api\ApiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ApiController extends AbstractController
{
    public function __construct(
        private readonly APiService $apiService,
    )
    {
    }

    #[Route(
        path:'/api/{method}',
        name: 'api',
        requirements: ['method' => '[a-z\-]+'],
        methods: ['POST'],
    )]
    public function index(string $method, Request $request): Response
    {
        $data = $this->apiService->collectData(method: $method, request: $request);

        return new JsonResponse($data);
    }
}
