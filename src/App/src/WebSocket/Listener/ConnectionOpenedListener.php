<?php

declare(strict_types=1);

namespace App\WebSocket\Listener;

use Settermjd\MezzioSwoole\WebSocket\Event\WebSocketMessageEvent;

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
    public function __invoke(WebSocketOpenEvent $event): void
    {
        $fd = $event->getRequest()->fd;

        $event->getServer()->push($fd, sprintf('connected: fd=%d', $fd));
    }
}
