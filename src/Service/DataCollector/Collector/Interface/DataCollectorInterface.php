<?php declare(strict_types=1);

namespace App\Service\DataCollector\Collector\Interface;

use App\Model\DataCollector\RequestDataInterface;
use App\Model\DataCollector\ResponseDataBag;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * @throws \Symfony\Component\Serializer\Exception\ExceptionInterface
 */
#[AutoconfigureTag('app.data_collector')]
interface DataCollectorInterface
{
    public function collect(RequestDataInterface $requestData, ResponseDataBag $data): void;
}
