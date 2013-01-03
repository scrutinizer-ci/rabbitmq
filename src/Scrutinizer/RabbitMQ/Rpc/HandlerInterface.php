<?php

namespace Scrutinizer\RabbitMQ\Rpc;

interface HandlerInterface
{
    public function getPayloadType();
    public function getCallback();
}