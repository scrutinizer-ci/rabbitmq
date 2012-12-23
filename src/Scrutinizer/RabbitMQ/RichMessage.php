<?php

namespace Scrutinizer\RabbitMQ;

use PhpAmqpLib\Message\AMQPMessage;

class RichMessage extends AMQPMessage
{
    public function __construct($messageObject)
    {
        parent::__contruct(serialize($messageObject), array('content_type' => 'application/php-serialize'));
    }
}