<?php

declare(strict_types=1);

namespace App\WebSocket\Listener;

use App\Service\OpenAiService;
use App\WebSocket\Table\ConnectionTable;
use App\WebSocket\Table\MessageTable;
use Psr\Log\LoggerInterface;
use Settermjd\MezzioSwoole\WebSocket\Event\WebSocketMessageEvent;
use Swoole\WebSocket\Server as SwooleWebSocketServer;

use function json_encode;
use function sprintf;

final class ConversationRelayMessageListener
{
    public function __construct(
        private readonly OpenAiService $openAi,
        private ConnectionTable $connectionTable,
        private MessageTable $messageTable,
        private readonly LoggerInterface $logger,
    ) {
    }

    private function writeLogMessage(string $callSid, string $message): void
    {
        $this->logger->info($callSid, ['message' => $message]);
    }

    public function __invoke(WebSocketMessageEvent $event): void
    {
        $frame = $event->getFrame();
        $fd    = $frame->fd;
        $data  = json_decode($frame->data, true);

        if (! is_array($data) || $data === []) {
            return;
        }

        $msgType = $data['type'] ?? '';

        switch ($msgType) {
            case 'setup':
                $callSid = $data['callSid'] ?? null;
                $this->connectionTable->set((string) $fd, ['callSid' => $callSid]);
                $this->writeLogMessage(
                    $callSid,
                    sprintf("[%s] Call connected\n", $callSid)
                );
                break;

            case 'prompt':
                $last = $data['last'] ?? '';
                if ($last === '') {
                    break;
                }

                $userText = $data['voicePrompt'] ?? '';
                $this->writeLogMessage(
                    $this->connectionTable->get((string) $fd, 'callSid'),
                    sprintf(
                        "[%s] Caller: {$userText}\n",
                        $this->connectionTable->get((string) $fd, 'callSid'),
                    ),
                );
                $this->messageTable->set((string) $fd, [
                    'role' => 'user',
                    'content' => $userText
                ]);

                $responseText = $this->streamResponse(
                    $event->getServer(),
                    $fd,
                    $this->connectionTable->get((string) $fd, 'callSid'),
                    iterator_to_array($this->messageTable),
                );
                $this->writeLogMessage(
                    $this->connectionTable->get((string) $fd, 'callSid'),
                    sprintf(
                        "[%s] Hoot: {$responseText}\n",
                        $this->connectionTable->get((string) $fd, 'callSid'),
                    )
                );
                $this->messageTable->set((string) $fd, [
                    'role' => 'assistant',
                    'content' => $responseText
                ]);
                break;

            case 'interrupt':
                $this->writeLogMessage(
                    $this->connectionTable->get((string) $fd, 'callSid'),
                    sprintf(
                        "[%s] Interrupted after: '%s'\n",
                        $this->connectionTable->get((string) $fd, 'callSid'),
                        $data['utteranceUntilInterrupt'] ?? ''
                    )
                );

                if ($this->messageTable->count() !== 0) {
                    $prev = $this->messageTable->offsetGet($this->messageTable->count() - 1);
                    if ($prev !== null && $prev['role'] === 'assistant') {
                        $this->messageTable->offsetUnset($this->messageTable->count());
                    }
                }
                break;

            case 'error':
                $this->writeLogMessage(
                    $this->connectionTable->get((string) $fd, 'callSid'),
                    sprintf(
                        "[%s] Conversation Relay error: %s\n",
                        $this->connectionTable->get((string) $fd, 'callSid'),
                        $data['description'] ?? ''
                    )
                );
                break;
        }
    }

    private function streamResponse(
        SwooleWebSocketServer $server,
        int $fd,
        ?string $callSid,
        array $messages = []
    ): string {
        $fullResponse = '';

        try {
            $fullResponse = $this->openAi->streamResponse(
                $messages,
                static function (string $token) use ($server, $fd): void {
                    $server->push(
                        $fd,
                        json_encode(
                            [
                                'type'  => 'text',
                                'token' => $token,
                                'last'  => false,
                            ]
                        )
                    );
                },
            );
        } finally {
            $server->push(
                $fd,
                json_encode(
                    [
                        'type'  => 'text',
                        'token' => '',
                        'last'  => true,
                    ]
                )
            );
        }

        return $fullResponse;
    }
}
