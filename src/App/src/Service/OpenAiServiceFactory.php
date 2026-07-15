<?php

declare(strict_types=1);

namespace App\Service;

use OpenAI;
use Psr\Container\ContainerInterface;
use RuntimeException;

use function getenv;

final class OpenAiServiceFactory
{
    public function __invoke(ContainerInterface $container): OpenAiService
    {
        $apiKey = $_ENV['OPENAI_API_KEY'] ?? getenv('OPENAI_API_KEY');
        if (empty($apiKey)) {
            throw new RuntimeException('OPENAI_API_KEY environment variable is not set.');
        }

        $systemPrompt = $container->get('config')['prompt'];

        return new OpenAiService(OpenAI::client($apiKey), $systemPrompt);
    }
}
