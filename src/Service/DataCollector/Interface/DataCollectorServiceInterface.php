<?php declare(strict_types=1);

namespace App\Service\DataCollector\Interface;

use App\Model\DataCollector\RequestDataInterface;
use App\Model\DataCollector\ResponseDataBag;

interface DataCollectorServiceInterface
{
    public function collect(RequestDataInterface $requestData): ResponseDataBag;
}
