<?php

namespace Scrutinizer\RabbitMQ\Rpc;

class Payload
{
    public $value;
    public $version;
    public $groups;

    public function __construct($value, $version = null, array $groups = array())
    {
        $this->value = $value;
        $this->version = $version;
        $this->groups = $groups;
    }
}