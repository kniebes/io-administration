<?php declare(strict_types=1);

namespace App\Service\ErrorLogger\Interface;

use Throwable;

interface ErrorLoggerInterface
{
    public const string TYPE_EMAIL = 'email';
    public const string TYPE_LOG  = 'log';
    public function log(Throwable $throwable, ?string $type = 'email'): void;
}
