<?php

namespace Scrutinizer\RabbitMQ\Rpc;

use JMS\Serializer\Annotation as Serializer;

class RpcError
{
    /** @Serializer\Type("string") */
    public $message;

    /** @Serializer\Type("array") */
    public $details;

    public function __construct($message, array $details = array())
    {
        $this->message = $message;
        $this->details = $details;
    }
}