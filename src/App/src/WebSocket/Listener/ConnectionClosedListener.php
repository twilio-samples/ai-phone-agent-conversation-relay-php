<?php

declare(strict_types=1);

namespace App\WebSocket\Listener;

use App\WebSocket\Table\ConnectionTable;
use App\WebSocket\Table\MessageTable;
use Settermjd\MezzioSwoole\WebSocket\Event\WebSocketCloseEvent;

final class ConnectionClosedListener
{
    public function __construct(
        private MessageTable $messageTable,
        private ConnectionTable $connectionTable,
    ) {
    }

    public function __invoke(WebSocketCloseEvent $event): void
    {
        $this->messageTable->del($event->getFd());
        $this->connectionTable->del($event->getFd());
    }
}
