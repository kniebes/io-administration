<?php declare(strict_types=1);

namespace App\Model\DataCollector;

class ResponseDataBag
{
    private array $data = [];
    private array $errors = [];

    private bool $hasErrors = false;

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

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function addError(string $errors): void
    {
        $this->errors[] = $errors;
        $this->hasErrors = true;
    }

    public function hasErrors(): bool
    {
        return $this->hasErrors;
    }
}
