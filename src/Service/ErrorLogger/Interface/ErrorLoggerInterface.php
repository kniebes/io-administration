<?php declare(strict_types=1);

namespace App\Service\ErrorLogger\Interface;

interface ErrorLoggerInterface
{
    public function log(string $subject, string $message, ?string $type = 'info'): void;
}
