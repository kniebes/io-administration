<?php declare(strict_types=1);

namespace App\MessageHandler;

use App\Message\SyndicationMessage;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class SyndicationMessageHandler
{
    public function __invoke(SyndicationMessage $message): void
    {

    }
}
