<?php declare(strict_types=1);

namespace App\Service\DataCollector\Collector;

use App\Entity\Blog;
use App\Model\DataCollector\ResponseDataBag;
use App\Service\DataCollector\Collector\Interface\DataCollectorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\SerializerInterface;

class BlogCollector implements DataCollectorInterface
{
    public function __construct(
        private SerializerInterface $serializer,
    ) {
    }

    /**
     * @throws \Symfony\Component\Serializer\Exception\ExceptionInterface
     */
    public function collect(Blog $blog, string $method, Request $request, ResponseDataBag $data): void
    {
        $serializedData = $this->serializer->serialize($blog, 'json');
        $data->setData('blog', json_decode($serializedData, true));
    }
}
