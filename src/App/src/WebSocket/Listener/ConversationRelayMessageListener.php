<?php

declare(strict_types=1);

namespace App\WebSocket\Listener;

use App\Service\OpenAiService;
use Settermjd\MezzioSwoole\WebSocket\Event\WebSocketMessageEvent;
use Swoole\WebSocket\Server as SwooleWebSocketServer;

use function json_encode;

final class ConversationRelayMessageListener
{
    private array $sessions = [];

    public function __construct(private readonly OpenAiService $openAi)
    {
    }

    private function writeLogMessage(WebSocketMessageEvent $event, string $message)
    {
        $event->getServer()->push($frame->fd, sprintf('echo: %s', $message));
    }

    public function __invoke(WebSocketMessageEvent $event): void
    {
        $frame = $event->getFrame();
        $fd = $frame->fd;

        $this->sessions[$fd] = ['callSid' => null, 'messages' => []];

        $data = json_decode($frame->data, true);

        if (! is_array($data)) {
            return;
        }

        $msgType = $data['type'] ?? '';
        $session = &$this->sessions[$fd];

        switch ($msgType) {
            case 'setup':
                $session['callSid'] = $data['callSid'] ?? null;
                $this->writeLogMessage($event, "[{$session['callSid']}] Call connected\n");
                break;

            case 'prompt':
                if (empty($data['last'])) {
                    break;
                }
                $userText = $data['voicePrompt'] ?? '';
                $this->writeLogMessage($event, "[{$session['callSid']}] Caller: {$userText}\n");

                $session['messages'][] = ['role' => 'user', 'content' => $userText];

                $responseText = $this->streamResponse(
                    $event->getServer(),
                    $fd,
                    $session['callSid'],
                    $session['messages'],
                );

                $session['messages'][] = ['role' => 'assistant', 'content' => $responseText];
                break;

            case 'interrupt':
                $spoken = $data['utteranceUntilInterrupt'] ?? '';
                $this->writeLogMessage($event, "[{$session['callSid']}] Interrupted after: '{$spoken}'\n");

                $last = end($session['messages']);
                if ($last !== false && $last['role'] === 'assistant') {
                    array_pop($session['messages']);
                }
                break;

            case 'error':
                $description = $data['description'] ?? '';
                $this->writeLogMessage($event, "[{$session['callSid']}] Conversation Relay error: {$description}\n");
                break;
        }

        $callSid = $this->sessions[$fd]['callSid'] ?? null;
        echo "[{$callSid}] Call ended\n";
        unset($this->sessions[$fd]);
    }

    private function streamResponse(SwooleWebSocketServer $server, int $fd, ?string $callSid, array $messages): string
    {
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

        $this->writeLogMessage($event, "[{$callSid}] Hoot: {$fullResponse}\n");

        return $fullResponse;
    }
}
