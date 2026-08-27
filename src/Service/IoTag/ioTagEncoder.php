<?php declare(strict_types=1);

namespace App\Service\IoTag;

use App\Service\IoTag\Encoder\Interface\IoTagEncoderInterface;

class ioTagEncoder
{
    /**
     * @param iterable<IoTagEncoderInterface> $handlers
     */
    public function __construct(
        private readonly iterable $handlers
    )
    {
    }

    public function encode(string $string): string
    {
        foreach ($this->handlers as $handler) {
            $string = $handler->encode($string);
        }

        return $string;
    }
}
