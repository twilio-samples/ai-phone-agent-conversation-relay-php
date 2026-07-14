<?php

declare(strict_types=1);

namespace App\Handler;

use Psr\Container\ContainerInterface;
use RuntimeException;

use function getenv;

final class TwimlHandlerFactory
{
    public function __invoke(ContainerInterface $container): TwimlHandler
    {
        $domain = $_ENV['DOMAIN'] ?? getenv('DOMAIN');
        if ($domain === '') {
            throw new RuntimeException('DOMAIN environment variable is not set.');
        }

        return new TwimlHandler($domain);
    }
}
