<?php

declare(strict_types=1);

namespace App\WebSocket\Table;

use Swoole\Table;

final class ConnectionTable extends Table
{
    public function __construct()
    {
        parent::__construct(1024);
        $this->column('callSid', self::TYPE_STRING, 34);
        $this->create();
    }
}
