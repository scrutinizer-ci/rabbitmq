<?php

require_once __DIR__ . '/../../../../../../vendor/autoload.php';

if ( ! isset($_SERVER['argv'][1])) {
    echo 'Connection DSN must be given.';
    exit(1);
}

$conDetails = \Scrutinizer\RabbitMQ\Util\DsnUtils::parse($_SERVER['argv'][1]);
$con = new \PhpAmqpLib\Connection\AMQPConnection($conDetails['host'], $conDetails['port'], $conDetails['user'], $conDetails['password'], $conDetails['path']);

$server = new \Scrutinizer\RabbitMQ\Rpc\RpcServer($con, \JMS\Serializer\SerializerBuilder::create()->build());
$server->setTestMode(true);
$server->run(
    'scrutinizer.rabbitmq.rpc_test',
    'string',
    function ($msg) {
        return strrev($msg);
    }
);