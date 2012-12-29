<?php

namespace Scrutinizer\RabbitMQ\Rpc;

use PhpAmqpLib\Connection\AMQPConnection;
use JMS\Serializer\Serializer;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Channel\AMQPChannel;

class RpcServer
{
    private $con;
    private $serializer;
    private $channel;
    private $testMode = false;

    public function __construct(AMQPConnection $con, Serializer $serializer)
    {
        $this->con = $con;
        $this->serializer = $serializer;
        $this->channel = $con->channel();
    }

    public function setTestMode($bool)
    {
        $this->testMode = (boolean) $bool;
    }

    public function run($queueName, $messageType, callable $handler)
    {
        $this->channel->queue_declare($queueName, false, ! $this->testMode, false, $this->testMode);
        $this->channel->basic_qos(0, 1, false);

        $this->channel->basic_consume($queueName, '', false, false, false, false,
            function(AMQPMessage $message) use ($messageType, $handler) {
                $payload = $this->serializer->deserialize($message->body, $messageType, 'json');
                $rs = call_user_func($handler, $payload);

                $this->channel->basic_ack($message->get('delivery_tag'));

                $replyMessage = new AMQPMessage($this->serializer->serialize($rs, 'json'), array(
                    'correlation_id' => $message->get('correlation_id'),
                ));

                $this->channel->basic_publish($replyMessage, '', $message->get('reply_to'));
            }
        );

        while (count($this->channel->callbacks) > 0) {
            $this->channel->wait();
        }
    }
}