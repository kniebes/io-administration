<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class SecurityHeadersSubscriber implements EventSubscriberInterface
{
    /** @var array<string, string> */
    private const array SECURITY_HEADERS = [
        'X-Frame-Options' => 'DENY',
        'X-Content-Type-Options' => 'nosniff',
        'Referrer-Policy' => 'strict-origin-when-cross-origin',
        'Content-Security-Policy' => "frame-ancestors 'none'",
    ];

    private const array EXCLUDED_PATH_PREFIXES = ['/_profiler', '/_wdt'];

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::RESPONSE => 'onKernelResponse'];
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $pathInfo = $event->getRequest()->getPathInfo();

        foreach (self::EXCLUDED_PATH_PREFIXES as $excludedPrefix) {
            if (str_starts_with($pathInfo, $excludedPrefix)) {
                return;
            }
        }

        $responseHeaders = $event->getResponse()->headers;

        foreach (self::SECURITY_HEADERS as $headerName => $headerValue) {
            if (!$responseHeaders->has($headerName)) {
                $responseHeaders->set(key: $headerName, values: $headerValue);
            }
        }
    }
}
