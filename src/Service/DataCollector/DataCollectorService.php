<?php declare(strict_types=1);

namespace App\Service\DataCollector;

use App\Entity\Blog;
use App\Model\DataCollector\RequestDataInterface;
use App\Model\DataCollector\ResponseDataBag;
use App\Service\DataCollector\Collector\Interface\DataCollectorInterface;
use App\Service\DataCollector\Interface\DataCollectorServiceInterface;
use Symfony\Component\HttpFoundation\Request;
use Throwable;

readonly class DataCollectorService implements DataCollectorServiceInterface
{
    /**
     * @param iterable<DataCollectorInterface> $handlers
     */
    public function __construct(
        private iterable $handlers,
    ) {
    }

    public function collect(Blog $blog, string $method, Request $request): ResponseDataBag
    {
        $data = new ResponseDataBag();
        foreach ($this->handlers as $handler) {
            try {
                $handler->collect(blog: $blog, method: $method, request: $request, data: $data);
            } catch (Throwable $throwable) {
                $data->addError($throwable->getMessage());
            }
        }

        return $data;
    }
}
