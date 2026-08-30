<?php declare(strict_types=1);

namespace App\Model\Ping;

readonly class PingResult
{
    public function __construct(
        private string $pingService,
        private string $result,
    )
    {
    }

    public function asArray(): array
    {
        return [
            'pingService' => $this->pingService,
            'result' => $this->result,
        ];
    }
}
