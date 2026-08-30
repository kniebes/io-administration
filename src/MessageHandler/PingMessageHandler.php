<?php declare(strict_types=1);

namespace App\MessageHandler;

use App\Message\PingMessage;
use App\Service\Ping\SendPing;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class PingMessageHandler
{
    public function __construct(
        private readonly SendPing $sendPing,
    )
    {
    }

    public function __invoke(PingMessage $message): void
    {
        $this->sendPing->send($message->getBlogId());
    }
}
