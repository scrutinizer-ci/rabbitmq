<?php

namespace Scrutinizer\RabbitMQ\Rpc;

class RawValue
{
    public $value;

    public function __construct($value)
    {
        $this->value = $value;
    }
}