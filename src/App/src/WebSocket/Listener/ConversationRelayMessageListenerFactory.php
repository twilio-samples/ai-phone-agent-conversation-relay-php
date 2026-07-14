<?php

declare(strict_types=1);

namespace App\WebSocket\Listener;

use App\Service\OpenAiService;
use App\WebSocket\Listener\ConversationRelayMessageListener;
use App\WebSocket\Table\ConnectionTable;
use App\WebSocket\Table\MessageTable;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

use function assert;

final class ConversationRelayMessageListenerFactory
{
    public function __invoke(ContainerInterface $container): ConversationRelayMessageListener
    {
        $openAiService = $container->get(OpenAiService::class);
        assert($openAiService instanceof OpenAiService);

        $messageTable = $container->get(MessageTable::class);
        assert($messageTable instanceof MessageTable);

        $connectionTable = $container->get(ConnectionTable::class);
        assert($connectionTable instanceof ConnectionTable);

        $logger = $container->get(LoggerInterface::class);
        assert($logger instanceof LoggerInterface);

        return new ConversationRelayMessageListener(
            openAi: $openAiService,
            connectionTable: $connectionTable,
            messageTable: $messageTable,
            logger: $logger,
        );
    }
}
