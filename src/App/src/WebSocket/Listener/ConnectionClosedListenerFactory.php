<?php

declare(strict_types=1);

namespace App\WebSocket\Listener;

use App\WebSocket\Table\MessageTable;
use Psr\Container\ContainerInterface;

final class ConnectionClosedListenerFactory
{
    public function __invoke(ContainerInterface $container): ConnectionClosedListener
    {
        $messageTable = $container->get(MessageTable::class);
        assert($messageTable instanceof MessageTable);

        return new ConnectionClosedListener($messageTable);
    }
}
