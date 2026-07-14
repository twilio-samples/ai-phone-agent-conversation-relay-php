<?php

declare(strict_types=1);

namespace App\WebSocket\Table;

use Swoole\Table;

final class MessageTable extends Table
{
    public function __construct()
    {
        parent::__construct(1024);
        $this->column('role', self::TYPE_STRING, 10);
        $this->column('content', self::TYPE_STRING, 10000);
        $this->create();
    }
}
