<?php declare(strict_types=1);

namespace App\Model\DataCollector;

class ResponseDataBag
{
    private array $data = [];

    public function getData(): array
    {
        return $this->data;
    }

    public function setData(string $key, array $data): void
    {
        if (array_key_exists($key, $this->data)) {
            throw new \LogicException('Data already exists');
        }
        $this->data[$key] = $data;
    }

    /**
     * @TODO Ist noch unsicher, ob es dafür einen Anwendungsfall gibt.
     */
    public function replaceData(string $key, array $data): void
    {
        $this->data[$key] = $data;
    }
}
