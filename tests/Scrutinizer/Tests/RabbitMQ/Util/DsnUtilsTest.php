<?php

namespace Scrutinizer\Tests\RabbitMQ\Util;

use Scrutinizer\RabbitMQ\Util\DsnUtils;

class DsnUtilsTest extends \PHPUnit_Framework_TestCase
{
    public function testParse()
    {
        $this->assertEquals(array(
            'host' => 'host',
            'user' => 'user',
            'password' => 'pass',
            'port' => 1345,
            'path' => '/foo',
        ), DsnUtils::parse('user:pass@host:1345/foo'));
    }
}