<?php

namespace Scrutinizer\RabbitMQ;

class LogMessage implements \Serializable
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

    public function serialize()
    {
        return serialize(array($this->level, $this->message, $this->context));
    }

    public function unserialize($str)
    {
        list($this->level, $this->message, $this->context) = unserialize($str);
    }
}