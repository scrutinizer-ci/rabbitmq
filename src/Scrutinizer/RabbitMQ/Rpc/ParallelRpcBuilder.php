<?php

namespace Scrutinizer\RabbitMQ\Rpc;

class ParallelRpcBuilder
{
    private $calls = array();

    public function schedule($queueName, $payload, $resultType, $id = null)
    {
        $call = array($queueName, $payload, $resultType);

        if (null === $id) {
            $this->calls[] = $call;
        } else {
            $this->calls[$id] = $call;
        }

        return $this;
    }

    public function getCalls()
    {
        return $this->calls;
    }
}