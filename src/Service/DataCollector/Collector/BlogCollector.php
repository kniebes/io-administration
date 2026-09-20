<?php declare(strict_types=1);

namespace App\Service\DataCollector\Collector;

use App\Entity\Blog;
use App\Model\ContentApi\RequestData;
use App\Model\DataCollector\ResponseDataBag;
use App\Service\DataCollector\Collector\Interface\DataCollectorInterface;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\SerializerInterface;

class BlogCollector implements DataCollectorInterface
{
    public function __construct(
        private SerializerInterface $serializer,
    )
    {
    }

    /**
     * @throws ExceptionInterface
     */
    public function collect(Blog $blog, string $method, RequestData $requestData, ResponseDataBag $data): void
    {
        if (!in_array(
            $method,
            [
                DataCollectorInterface::METHOD_BLOG_POST,
                DataCollectorInterface::METHOD_BLOG_POSTS,
                DataCollectorInterface::METHOD_PAGE,
            ],
            true
        )) {
            return;
        }

        $serializedData = $this->serializer->serialize(
            data: $blog,
            format: 'json',
            context: [
                'groups' => ['blog_post:read'],
                'config' => $requestData->getConfig(),
            ]
        );
        $data->setData('blog', json_decode($serializedData, true));
    }
}
