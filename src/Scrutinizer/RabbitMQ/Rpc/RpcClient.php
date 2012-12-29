<?php

namespace Scrutinizer\RabbitMQ\Rpc;

use PhpAmqpLib\Connection\AMQPConnection;
use JMS\Serializer\Serializer;
use PhpAmqpLib\Message\AMQPMessage;

class RpcClient
{
    private $con;
    private $serializer;
    private $channel;
    private $callbackQueue;
    private $correlationIds = array();

    public function __construct(AMQPConnection $con, Serializer $serializer)
    {
        $this->con = $con;
        $this->serializer = $serializer;
        $this->channel = $con->channel();

        // Exclusive, Auto-Ack, Non-Passive, Non-Durable
        list($this->callbackQueue,,) = $this->channel->queue_declare('', false, false, true);
    }

    /**
     * Invokes a single remote command and returns the result of the invocation.
     *
     * The remote command is invoked synchronously.
     *
     * @param $queueName
     * @param $payload
     * @param $resultType
     *
     * @return mixed
     */
    public function invoke($queueName, $payload, $resultType)
    {
        // Worker queue
        $this->channel->queue_declare($queueName, false, true, false, false);

        $message = new AMQPMessage($this->serializer->serialize($payload, 'json'), array(
            'correlation_id' => $correlationId = $this->getCorrelationId(),
            'reply_to' => $this->callbackQueue,
        ));
        $this->channel->basic_publish($message, '', $queueName);

        $resultReceived = false;
        $result = null;
        $this->channel->basic_consume($this->callbackQueue, '', false, $noAck = true, false, false,
            function(AMQPMessage $message) use (&$result, &$resultReceived, $correlationId, $resultType) {
                if ($correlationId !== $message->get('correlation_id')) {
                    return;
                }

                $result = $this->serializer->deserialize($message->body, $resultType, 'json');
                $resultReceived = true;
            }
        );

        while (count($this->channel->callbacks) > 0 && $resultReceived === false) {
            $this->channel->wait();
        }

        return $result;
    }

    private function getCorrelationId()
    {
        do {
            $corId = uniqid($this->callbackQueue, true);
        } while (isset($this->correlationIds[$corId]));

        $this->correlationIds[$corId] = true;

        return $corId;
    }
}