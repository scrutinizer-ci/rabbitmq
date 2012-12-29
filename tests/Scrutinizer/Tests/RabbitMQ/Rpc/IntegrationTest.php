<?php

namespace Scrutinizer\Tests\RabbitMQ\Rpc;

use Symfony\Component\Process\Process;
use Scrutinizer\RabbitMQ\Rpc\RpcClient;
use PhpAmqpLib\Connection\AMQPConnection;
use Scrutinizer\RabbitMQ\Util\DsnUtils;
use JMS\Serializer\SerializerBuilder;
use PhpAmqpLib\Helper\MiscHelper;

class IntegrationTest extends \PHPUnit_Framework_TestCase
{
    /** @var AMQPConnection */
    private $con;
    private $serializer;

    public function testClient()
    {
        $proc = new Process('php scripts/server.php '.escapeshellarg($_SERVER['RABBITMQ_DSN']), __DIR__);
        $proc->start();

        $client = new RpcClient($this->con, $this->serializer);
        $client->setTestMode(true);

        $this->assertEquals('oof', $client->invoke('scrutinizer.rabbitmq.rpc_test', 'foo', 'string'));
        $this->assertEquals('rab', $client->invoke('scrutinizer.rabbitmq.rpc_test', 'bar', 'string'));

        $proc->stop(1);
    }

    protected function setUp()
    {
        $details = DsnUtils::parse($_SERVER['RABBITMQ_DSN']);
        $this->con = new AMQPConnection($details['host'], $details['port'], $details['user'], $details['password'], $details['path']);

        $this->serializer = SerializerBuilder::create()->build();
    }
}