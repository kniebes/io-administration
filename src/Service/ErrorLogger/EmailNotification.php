<?php declare(strict_types=1);

namespace App\Service\ErrorLogger;

use App\Service\ErrorLogger\Interface\ErrorLoggerInterface;
use Psr\Log\LoggerInterface;
use Throwable;

final readonly class EmailNotification implements ErrorLoggerInterface
{
    public function __construct(
        private LoggerInterface $logger,
    )
    {
    }

    public function log(Throwable $throwable, ?string $type = 'email'): void
    {
        match ($type) {
            ErrorLoggerInterface::TYPE_EMAIL => $this->sendEmailMessage($throwable),
            default => $this->logMessage($throwable),
        };
    }
    protected function sendEmailMessage(Throwable $throwable, ?string $type = 'email'): void
    {
        $subject = $throwable->getMessage();
        $message = [
            'file' => $throwable->getFile(),
            'line' => $throwable->getLine(),
            'trace' => $throwable->getTraceAsString(),
        ];

        $header = 'From: notification@kniebes.com' . "\r\n" .
            'Reply-To: notification@kniebes.com' . "\r\n" .
            'Content-Type: text/plain; charset=UTF-8' . "\r\n" .
            'X-Mailer: PHP/' . phpversion();

        mail('m@kniebes.io', sprintf('[%s] %s', $type, $subject), implode(PHP_EOL, $message), $header);
    }

    protected function logMessage(Throwable $throwable, ?string $type = 'email'): void
    {
        $this->logger->error(message: $throwable->getMessage(), context: [
            'file' => $throwable->getFile(),
            'line' => $throwable->getLine(),
            'trace' => $throwable->getTraceAsString(),
        ]);
    }
}
