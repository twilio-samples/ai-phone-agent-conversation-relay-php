<?php

declare(strict_types=1);

namespace App\WebSocket\Listener;

use App\Service\OpenAiService;
use App\WebSocket\Listener\ConversationRelayMessageListener;
use Psr\Container\ContainerInterface;

use function assert;

final class ConversationRelayMessageListenerFactory
{
    public function __invoke(ContainerInterface $container): ConversationRelayMessageListener
    {
        $openAiService = $container->get(OpenAiService::class);
        assert($openAiService instanceof OpenAiService);

        return new ConversationRelayMessageListener(
            openAi: $openAiService,
        );
    }
}
