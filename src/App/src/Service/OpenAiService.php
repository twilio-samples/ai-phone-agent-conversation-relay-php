<?php

declare(strict_types=1);

namespace App\Service;

use OpenAI\Client;

use function array_map;
use function array_merge;
use function array_values;
use function json_decode;
use function strtoupper;

final class OpenAiService
{
    private const MODEL = 'gpt-4o-mini';

    private const TOOLS = [
        [
            'type'     => 'function',
            'function' => [
                'name'        => 'lookup_flight_status',
                'description' => 'Look up the current status of an Owl Air flight by flight number.',
                'parameters'  => [
                    'type'       => 'object',
                    'properties' => [
                        'flight_number' => [
                            'type'        => 'string',
                            'description' => 'The Owl Air flight number, e.g. OA101',
                        ],
                    ],
                    'required'   => ['flight_number'],
                ],
            ],
        ],
    ];

    private const MOCK_FLIGHT_DATA = [
        'OA101' => ['status' => 'on time',                 'gate' => 'B12', 'departure' => 'two thirty PM'],
        'OA205' => ['status' => 'delayed by forty minutes', 'gate' => 'C4',  'departure' => 'five fifteen PM'],
        'OA318' => ['status' => 'cancelled'],
    ];

    public function __construct(private readonly Client $client, private readonly string $systemPrompt)
    {
    }

    public function streamResponse(array $messages, callable $onToken): string
    {
        $openAiMessages = array_merge(
            [
                [
                    'content' => $this->systemPrompt,
                    'role'    => 'system',
                ],
            ],
            $messages,
        );

        $stream = $this->client->chat()->createStreamed([
            'model'      => self::MODEL,
            'max_tokens' => 300,
            'messages'   => $openAiMessages,
            'tools'      => self::TOOLS,
        ]);

        $fullResponse = '';
        $finishReason = null;
        $toolCallsAcc = [];  // index => ['id' => '', 'name' => '', 'arguments' => '']

        foreach ($stream as $response) {
            $choice       = $response->choices[0];
            $delta        = $choice->delta;
            $finishReason = $choice->finishReason ?? $finishReason;

            if ($delta->content !== null) {
                $fullResponse .= $delta->content;
                ($onToken)($delta->content);
            }

            foreach ($delta->toolCalls ?? [] as $tc) {
                if (! isset($toolCallsAcc[$tc->index])) {
                    $toolCallsAcc[$tc->index] = ['id' => '', 'name' => '', 'arguments' => ''];
                }
                if ($tc->id !== null) {
                    $toolCallsAcc[$tc->index]['id'] = $tc->id;
                }
                if ($tc->function?->name !== null) {
                    $toolCallsAcc[$tc->index]['name'] = $tc->function->name;
                }
                if ($tc->function?->arguments !== null) {
                    $toolCallsAcc[$tc->index]['arguments'] .= $tc->function->arguments;
                }
            }
        }

        if ($finishReason === 'tool_calls' && ! empty($toolCallsAcc)) {
            $toolCalls = array_values($toolCallsAcc);

            $openAiMessages[] = [
                'role'       => 'assistant',
                'tool_calls' => array_map(
                    static fn (array $tc): array => [
                        'id'       => $tc['id'],
                        'type'     => 'function',
                        'function' => ['name' => $tc['name'], 'arguments' => $tc['arguments']],
                    ],
                    $toolCalls,
                ),
            ];

            foreach ($toolCalls as $tc) {
                $args   = json_decode($tc['arguments'], true);
                $result = $this->executeTool($tc['name'], $args);

                $openAiMessages[] = [
                    'role'         => 'tool',
                    'tool_call_id' => $tc['id'],
                    'content'      => $result,
                ];
            }

            $fullResponse = '';
            $stream2      = $this->client->chat()->createStreamed([
                'model'      => self::MODEL,
                'max_tokens' => 400,
                'messages'   => $openAiMessages,
            ]);

            foreach ($stream2 as $response) {
                $delta = $response->choices[0]->delta;
                if ($delta->content !== null) {
                    $fullResponse .= $delta->content;
                    ($onToken)($delta->content);
                }
            }
        }

        return $fullResponse;
    }

    private function executeTool(string $name, array $args): string
    {
        if ($name !== 'lookup_flight_status') {
            return 'Unknown tool.';
        }

        $flightNumber = strtoupper($args['flight_number'] ?? '');
        $flight       = self::MOCK_FLIGHT_DATA[$flightNumber] ?? null;

        if ($flight === null) {
            return 'No flight found with that number.';
        }

        if ($flight['status'] === 'cancelled') {
            return 'That flight has been cancelled.';
        }

        return "Flight {$flightNumber} is {$flight['status']}, "
            . "departing at {$flight['departure']} from gate {$flight['gate']}.";
    }
}
