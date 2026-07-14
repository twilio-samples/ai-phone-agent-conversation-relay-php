<?php

declare(strict_types=1);

namespace App\Factory;

use Monolog\Handler\ErrorLogHandler;
use Monolog\Level;
use Monolog\Logger;
use Monolog\Processor\PsrLogMessageProcessor;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class LoggerFactory
{
    public function __invoke(ContainerInterface $container): LoggerInterface
    {
        $logger = new Logger('swoole-http-server');
        $logger->pushHandler(new ErrorLogHandler(
            ErrorLogHandler::OPERATING_SYSTEM,
            Level::Debug,
            true,
            true,
        ));
        $logger->pushProcessor(new PsrLogMessageProcessor());

        return $logger;
    }
}
