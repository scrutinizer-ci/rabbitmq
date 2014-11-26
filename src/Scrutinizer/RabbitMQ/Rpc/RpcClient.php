<?php

declare(ticks = 1000);

namespace Scrutinizer\RabbitMQ\Rpc;

use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerBuilder;
use PhpAmqpLib\Connection\AMQPConnection;
use JMS\Serializer\Serializer;
use PhpAmqpLib\Message\AMQPMessage;
use JMS\Serializer\Metadata\ClassMetadata;
use JMS\Serializer\Metadata\PropertyMetadata;
use JMS\Serializer\Exclusion\VersionExclusionStrategy;
use JMS\Serializer\Exclusion\GroupsExclusionStrategy;

class RpcClient
{
    private $con;
    private $serializer;
    private $channel;
    private $callbackQueue;
    private $testMode = false;
    private $initialized = false;

    private $rpcCalls = array();

    public function __construct(AMQPConnection $con, Serializer $serializer = null)
    {
        $this->con = $con;
        $this->serializer = $serializer ?: SerializerBuilder::create()->build();
    }

    private function initialize()
    {
        if ($this->initialized) {
            return;
        }
        $this->initialized = true;

        $this->channel = $this->con->channel();

        // Exclusive, Auto-Ack, Non-Passive, Non-Durable
        list($this->callbackQueue,,) = $this->channel->queue_declare('', false, false, true, true);
        $this->channel->basic_consume($this->callbackQueue, '', false, $noAck = true, false, false,
            function(AMQPMessage $message) {
                $correlationId = $message->get('correlation_id');

                if ( ! isset($this->rpcCalls[$correlationId])) {
                    return;
                }

                if (0 === strpos($message->body, 'scrutinizer.rpc_error:')) {
                    $msgBody = substr($message->body, strlen('scrutinizer.rpc_error:'));
                    $resultType = 'Scrutinizer\RabbitMQ\Rpc\RpcError';
                } else {
                    $msgBody = $message->body;
                    $resultType = $this->rpcCalls[$correlationId]['result_type'];
                }

                $this->rpcCalls[$correlationId]['result'] = $this->serializer->deserialize($msgBody, $resultType, 'json');
                $this->rpcCalls[$correlationId]['result_received'] = true;
            }
        );
    }

    public function getChannel()
    {
        $this->initialize();

        return $this->channel;
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
     * @throws RpcErrorException when a remote error occurs
     *
     * @return mixed
     */
    public function invoke($queueName, $payload, $resultType, $timeout = 10)
    {
        $rs = $this->invokeAll(array(array($queueName, $payload, $resultType)), $timeout);
        if ($rs[0] instanceof RpcError) {
            throw new RpcErrorException($rs[0]);
        }

        return $rs[0];
    }

    public function invokeAll(array $calls, $timeout = 10)
    {
        if (empty($calls)) {
            throw new \InvalidArgumentException('$calls must not be empty.');
        }

        $this->initialize();

        $correlationIds = array();
        foreach ($calls as $k => $call) {
            list($queueName, $payload, $resultType) = $call;

            // Worker Queue
            $this->channel->queue_declare($queueName, false, ! $this->testMode, false, $this->testMode);

            $context = new SerializationContext();
            if ($payload instanceof Payload) {
                if ( ! empty($payload->version)) {
                    $context->setVersion($payload->version);
                }
                if ( ! empty($payload->groups)) {
                    $context->setGroups($payload->groups);
                }

                $payload = $payload->value;
            }

            $message = new AMQPMessage($this->serializer->serialize($payload, 'json', $context), array(
                'correlation_id' => $correlationId = $this->getCorrelationId(),
                'reply_to' => $this->callbackQueue,
            ));
            $this->channel->basic_publish($message, '', $queueName);

            $this->rpcCalls[$correlationId] = array(
                'result_received' => false,
                'result' => null,
                'result_type' => $resultType,
            );
            $correlationIds[$k] = $correlationId;
        }

        $start = time();
        while ($start + $timeout > time() && count($this->channel->callbacks) > 0 && $this->hasMissingResult($correlationIds)) {
            $this->channel->wait(null, true);
            usleep(1E5); // 100 ms
        }

        $results = array();
        foreach ($correlationIds as $k => $correlationId) {
            if ($this->rpcCalls[$correlationId]['result_received'] === false) {
                throw new \RuntimeException(sprintf('Could not retrieve result for correlation id %s.', $correlationId));
            }

            $results[$k] = $this->rpcCalls[$correlationId]['result'];
            unset($this->rpcCalls[$correlationId]);
        }

        return $results;
    }

    private function hasMissingResult(array $correlationIds)
    {
        foreach ($correlationIds as $correlationId) {
            if ($this->rpcCalls[$correlationId]['result_received'] === false) {
                return true;
            }
        }

        return false;
    }

    private function getCorrelationId()
    {
        do {
            $corId = uniqid('', false);
        } while (isset($this->rpcCalls[$corId]));

        return $corId;
    }
}