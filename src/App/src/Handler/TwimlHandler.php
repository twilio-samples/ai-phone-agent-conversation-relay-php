<?php

declare(strict_types=1);

namespace App\Handler;

use Laminas\Diactoros\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Twilio\TwiML\VoiceResponse;

final class TwimlHandler implements RequestHandlerInterface
{
    public function __construct(private readonly string $domain)
    {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $voiceResponse = new VoiceResponse();
        $connect       = $voiceResponse->connect();
        $connect->conversationRelay(
            [
                'url'                         => "wss://{$this->domain}",
                'welcomeGreeting'             => <<<EOF
Thanks for calling Owl Air! I'm Hoot.
I can help with flight status, baggage policy, loyalty points, or booking changes.
Which of those can I help you with?
EOF,
                'ttsProvider'                 => 'ElevenLabs',
                'transcriptionProvider'       => 'Deepgram',
                'speechModel'                 => 'nova-3-general',
                'eotThreshold'                => '0.8',
                'ignoreBackchannel'           => 'true',
                'hints'                       => 'Owl Air, loyalty points, baggage, carry-on, check-in, boarding pass',
                'interruptible'               => 'any',
                'interruptSensitivity'        => 'medium',
                'elevenlabsTextNormalization' => 'auto',
            ]
        );

        $response = new Response();
        $response->getBody()->write((string) $voiceResponse);

        return $response->withHeader('Content-Type', 'text/xml');
    }
}
