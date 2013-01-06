<?php

namespace Scrutinizer\RabbitMQ\Rpc;

class RpcErrorException extends \RuntimeException
{
    private $rpcError;

    public function __construct(RpcError $error)
    {
        parent::__construct($error->message);

        $this->rpcError = $error;
    }

    public function getRpcError()
    {
        return $this->rpcError;
    }
}