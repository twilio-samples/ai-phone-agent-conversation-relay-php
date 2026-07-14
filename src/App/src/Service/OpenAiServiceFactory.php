<?php

declare(strict_types=1);

namespace App\Service;

use OpenAI;
use RuntimeException;

use function getenv;

final class OpenAiServiceFactory
{
    public function __invoke(): OpenAiService
    {
        $apiKey = $_ENV['OPENAI_API_KEY'] ?? getenv('OPENAI_API_KEY');
        if (empty($apiKey)) {
            throw new RuntimeException('OPENAI_API_KEY environment variable is not set.');
        }

        return new OpenAiService(OpenAI::client($apiKey));
    }
}
