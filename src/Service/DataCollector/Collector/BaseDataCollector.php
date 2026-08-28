<?php declare(strict_types=1);

namespace App\Service\DataCollector\Collector;

use App\Model\DataCollector\RequestDataInterface;
use App\Model\DataCollector\ResponseDataBag;
use App\Service\DataCollector\Collector\Interface\DataCollectorInterface;

class BaseDataCollector implements DataCollectorInterface
{
    public function collect(RequestDataInterface $requestData, ResponseDataBag $data): void
    {
        $data->setData('base_data', $this->getBaseData());
    }

    private function getBaseData(): array
    {
        return [];
    }
}
