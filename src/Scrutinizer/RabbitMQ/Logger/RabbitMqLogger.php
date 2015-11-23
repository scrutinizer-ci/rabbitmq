<?php

namespace Scrutinizer\RabbitMQ\Logger;

use JMS\Serializer\SerializerBuilder;
use PhpAmqpLib\Connection\AbstractConnection;
use Psr\Log\AbstractLogger;
use JMS\Serializer\Serializer;
use PhpAmqpLib\Message\AMQPMessage;

class RabbitMqLogger extends AbstractLogger
{
    private $con;
    private $channel;
    private $serializer;
    private $topic;
    private $defaultContext;

    public function __construct(AbstractConnection $con, Serializer $serializer = null, $topic = null, array $defaultContext = array())
    {
        $this->con = $con;
        $this->channel = $con->channel();
        $this->channel->exchange_declare('scrutinizer.logs', 'topic');

        $this->serializer = $serializer ?: SerializerBuilder::create()->build();

        if (false !== strpos($topic, '.')) {
            throw new \InvalidArgumentException(sprintf('Topic must not contain dots, but got "%s".', $topic));
        }
        $this->topic = $topic;

        $this->defaultContext = $defaultContext;
    }

    public function log($level, $message, array $context = array())
    {
        $context = array_merge($this->defaultContext, $context);

        $message = new AMQPMessage($this->serializer->serialize(array(
            'message' => (string) $message,
            'context' => $context,
        ), 'json'));

        $this->channel->basic_publish($message, 'scrutinizer.logs', ($this->topic ?: 'anonymous').'.'.$level);
    }
}