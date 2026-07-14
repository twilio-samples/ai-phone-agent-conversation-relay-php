<?php

declare(strict_types=1);

namespace App;

/**
 * The configuration provider for the App module
 *
 * @see https://docs.laminas.dev/laminas-component-installer/
 */
class ConfigProvider
{
    /**
     * Returns the configuration array
     *
     * To add a bit of a structure, each section is defined in a separate
     * method which returns an array with its configuration.
     */
    public function __invoke(): array
    {
        return [
            'dependencies' => $this->getDependencies(),
            'mezzio-swoole' => $this->getSwooleConfig(),
            'templates'    => $this->getTemplates(),
        ];
    }

    /**
     * Returns the container dependencies
     */
    public function getDependencies(): array
    {
        return [
            'invokables' => [
                Handler\PingHandler::class => Handler\PingHandler::class,
                WebSocket\Listener\ConnectionOpenedListener::class => WebSocket\Listener\ConnectionOpenedListener::class,

            ],
            'factories'  => [
                Handler\TwimlHandler::class => Handler\TwimlHandlerFactory::class,
                Service\OpenAiService::class   => Service\OpenAiServiceFactory::class,
                WebSocket\Listener\ConversationRelayMessageListener::class => WebSocket\Listener\ConversationRelayMessageListenerFactory::class,
            ],
        ];
    }

    /**
     * Returns the templates configuration
     */
    public function getTemplates(): array
    {
        return [
            'paths' => [
                'app'    => [__DIR__ . '/../templates/app'],
                'error'  => [__DIR__ . '/../templates/error'],
                'layout' => [__DIR__ . '/../templates/layout'],
            ],
        ];
    }

    public function getSwooleConfig(): array
    {
        return [
            'swoole-http-server' => [
                'listeners' => [
                    Settermjd\MezzioSwoole\WebSocket\Event\WebSocketOpenEvent::class => [
                        WebSocket\Listener\ConnectionOpenedListener::class,
                    ],
                    Settermjd\MezzioSwoole\WebSocket\Event\WebSocketMessageEvent::class => [
                        WebSocket\Listener\ConversationRelayMessageListener::class,
                    ],
                ],
            ],
        ];
    }

}
