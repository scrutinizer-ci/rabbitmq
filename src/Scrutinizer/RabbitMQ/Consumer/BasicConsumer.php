<?php

namespace Scrutinizer\RabbitMQ\Consumer;

use JMS\Serializer\Serializer;
use JMS\Serializer\SerializerBuilder;
use PhpAmqpLib\Connection\AMQPConnection;
use PhpAmqpLib\Message\AMQPMessage;

abstract class BasicConsumer
{
    private $con;
    private $channel;
    private $queueName;
    private $serializer;

    public function __construct(AMQPConnection $con, $queueName, Serializer $serializer = null)
    {
        $this->con = $con;
        $this->channel = $con->channel();
        $this->queueName = $queueName;
        $this->serializer = $serializer ?: SerializerBuilder::create()->build();
    }

    public function run()
    {
        $this->channel->queue_declare($this->queueName, false, true, false, false);
        $this->channel->basic_consume($this->queueName, '', false, false, false, false, array($this, 'consume'));

        while (count($this->channel->callbacks) > 0) {
            $this->channel->wait();
        }
    }

    public function consume(AMQPMessage $message)
    {
        $payload = $this->serializer->deserialize($message->body, $this->getPayloadType(), 'json');

        $this->consumeInternal($payload);
    }

    abstract public function getPayloadType();
    abstract protected function consumeInternal($payload);
}