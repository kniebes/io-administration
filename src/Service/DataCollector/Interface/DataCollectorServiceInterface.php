<?php declare(strict_types=1);

namespace App\Service\DataCollector\Interface;

use App\Entity\Blog;
use App\Model\DataCollector\RequestDataInterface;
use App\Model\DataCollector\ResponseDataBag;
use Symfony\Component\HttpFoundation\Request;

interface DataCollectorServiceInterface
{
    public function collect(Blog $blog, string $method, Request $request): ResponseDataBag;
}
