<?php

namespace Scrutinizer\Tests\RabbitMQ\Util;

use PHPUnit\Framework\TestCase;
use Scrutinizer\RabbitMQ\Util\DsnUtils;

class DsnUtilsTest extends TestCase
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
