<?php

declare(strict_types=1);

namespace App\WebSocket\Listener;

use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

final class ConnectionOpenedListenerFactory
{
    public function __invoke(ContainerInterface $container): ConnectionOpenedListener
    {
        $logger = $container->get(LoggerInterface::class);
        assert($logger instanceof LoggerInterface);

        return new ConnectionOpenedListener(logger: $logger);
    }
}
