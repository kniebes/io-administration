<?php declare(strict_types=1);

namespace App\Service\DataCollector;

use App\Model\DataCollector\RequestDataInterface;
use App\Model\DataCollector\ResponseDataBag;
use App\Service\DataCollector\Collector\Interface\DataCollectorInterface;
use App\Service\DataCollector\Interface\DataCollectorServiceInterface;

readonly class DataCollectorService implements DataCollectorServiceInterface
{
    /**
     * @param iterable<DataCollectorInterface> $handlers
     */
    public function __construct(
        private iterable $handlers
    )
    {
    }

    public function collect(RequestDataInterface $requestData): ResponseDataBag
    {
        $data = new  ResponseDataBag();
        foreach ($this->handlers as $handler) {
            $handler->collect(requestData: $requestData, data: $data);
        }

        return $data;
    }
}
