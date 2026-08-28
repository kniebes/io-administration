<?php declare(strict_types=1);

namespace App\Service\DataCollector\Collector;

use App\Model\DataCollector\RequestDataInterface;
use App\Model\DataCollector\ResponseDataBag;
use App\Service\DataCollector\Collector\Interface\DataCollectorInterface;

readonly class BaseDataCollector implements DataCollectorInterface
{
    public function __construct(
        private BaseDataConfig $baseDataConfig,
    )
    {
    }

    public function collect(RequestDataInterface $requestData, ResponseDataBag $data): void
    {
        $data->setData('baseData', $this->getBaseData());
    }

    private function getBaseData(): array
    {
        return get_object_vars($this->baseDataConfig);
    }
}
