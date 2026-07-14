<?php

declare(strict_types=1);

namespace App\WebSocket\Listener;

use Psr\Log\LoggerInterface;
use Settermjd\MezzioSwoole\WebSocket\Event\WebSocketOpenEvent;

use function sprintf;

/**
 * Example listener: pushes an acknowledgement frame back to a client whose
 * connection has just been accepted.
 *
 * Registered against WebSocketOpenEvent::class via
 * `mezzio-swoole.swoole-http-server.listeners` in App\ConfigProvider.
 */
final class ConnectionOpenedListener
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(WebSocketOpenEvent $event): void
    {
        $fd = $event->getRequest()->fd;
        $this->logger->info(sprintf('connected: fd=%d', $fd));
    }
}
