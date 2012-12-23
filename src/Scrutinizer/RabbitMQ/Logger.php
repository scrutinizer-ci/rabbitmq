<?php

namespace Scrutinizer\RabbitMQ;

use Psr\Log\AbstractLogger;
use PhpAmqpLib\Connection\AMQPConnection;

class Logger extends AbstractLogger
{
    private $con;
    private $channel;

    public function __construct(AMQPConnection $con)
    {
        $this->con = $con;
        $this->channel = $con->channel();
        $this->channel->exchange_declare('worker_log', 'fanout');
    }

    public function log($level, $message, array $context = array())
    {
        $this->channel->basic_publish(new RichMessage(new LogMessage($level, $message, $context)));
    }
}