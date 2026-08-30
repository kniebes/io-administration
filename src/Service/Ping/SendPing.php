<?php declare(strict_types=1);

namespace App\Service\Ping;

use App\Entity\Blog;
use App\Model\Ping\PingConfiguration;
use App\Repository\BlogRepository;
use App\Service\Ping\PingConfiguration\Interface\PingConfigurationFactoryInterface;
use App\Service\Ping\PingService\Interface\PingServiceInterface;

readonly class SendPing
{
    /**
     * @param iterable<PingServiceInterface> $handlers
     */
    public function __construct(
        private iterable $handlers,
        private readonly PingConfigurationFactoryInterface $pingConfigurationFactory,
        private readonly BlogRepository $blogRepository,
    )
    {
    }

    public function send(int $blogId): void
    {
        $blog = $this->blogRepository->find($blogId);
        if (!$blog instanceof Blog) {
            return;
        }

        $pingServices = $blog->getPingServices();
        if (empty($pingServices)) {
            return;
        }

        $pingConfigurations = $this->pingConfigurationFactory->create($blog);
        $pingResult = [];
        foreach ($pingConfigurations as $pingConfiguration) {
            $this->invokePing(pingConfiguration: $pingConfiguration, pingResult: $pingResult);
        }
    }

    private function invokePing(PingConfiguration $pingConfiguration, array &$pingResult): void
    {
        foreach ($this->handlers as $handler) {
            if ($handler instanceof PingServiceInterface && $handler->supports($pingConfiguration)) {
                $pingResult[] = $handler->ping($pingConfiguration);
            }
        }
    }
}
