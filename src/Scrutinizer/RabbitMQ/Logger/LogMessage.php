<?php

namespace Scrutinizer\RabbitMQ\Logger;

class LogMessage
{
    private $level;
    private $message;
    private $context;

    public function __construct($level, $message, array $context)
    {
        $this->level = $level;
        $this->message = $message;
        $this->context = $context;
    }
}