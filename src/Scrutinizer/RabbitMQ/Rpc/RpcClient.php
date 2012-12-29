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
    private $testMode = false;

    private $rpcCalls = array();

    public function __construct(AMQPConnection $con, Serializer $serializer)
    {
        $this->con = $con;
        $this->serializer = $serializer;
        $this->channel = $con->channel();

        // Exclusive, Auto-Ack, Non-Passive, Non-Durable
        list($this->callbackQueue,,) = $this->channel->queue_declare('', false, false, true, true);
        $this->channel->basic_consume($this->callbackQueue, '', false, $noAck = true, false, false,
            function(AMQPMessage $message) {
                $correlationId = $message->get('correlation_id');

                if ( ! isset($this->rpcCalls[$correlationId])) {
                    return;
                }

                $this->rpcCalls[$correlationId]['result'] = $this->serializer->deserialize($message->body, $this->rpcCalls[$correlationId]['result_type'], 'json');
                $this->rpcCalls[$correlationId]['result_received'] = true;
            }
        );
    }

    public function setTestMode($bool)
    {
        $this->testMode = (boolean) $bool;
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
        $this->channel->queue_declare($queueName, false, ! $this->testMode, false, $this->testMode);

        $message = new AMQPMessage($this->serializer->serialize($payload, 'json'), array(
            'correlation_id' => $correlationId = $this->getCorrelationId(),
            'reply_to' => $this->callbackQueue,
        ));
        $this->channel->basic_publish($message, '', $queueName);

        $this->rpcCalls[$correlationId] = array(
            'result_received' => false,
            'result' => null,
            'result_type' => $resultType,
        );

        while (count($this->channel->callbacks) > 0 && $this->rpcCalls[$correlationId]['result_received'] === false) {
            $this->channel->wait();
        }

        if ( ! $this->rpcCalls[$correlationId]['result_received']) {
            unset($this->rpcCalls[$correlationId]);

            throw new \RuntimeException('Could not retrieve result from remote server.');
        }

        $result = $this->rpcCalls[$correlationId]['result'];
        unset($this->rpcCalls[$correlationId]);

        return $result;
    }

    private function getCorrelationId()
    {
        do {
            $corId = uniqid('', false);
        } while (isset($this->rpcCalls[$corId]));

        return $corId;
    }
}