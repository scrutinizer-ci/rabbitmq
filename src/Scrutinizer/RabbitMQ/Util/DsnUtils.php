<?php

namespace Scrutinizer\RabbitMQ\Util;

use Scrutinizer\RabbitMQ\RetryConnection;
use Scrutinizer\RabbitMQ\RetrySSLConnection;

abstract class DsnUtils
{
    public static function parse($dsn)
    {
        if ( ! preg_match('#^([^:]+):([^@]+)@([^:]+):([0-9]+)(/.*)$#', $dsn, $match)) {
            throw new \InvalidArgumentException(sprintf('Could not parse DSN "%s".', $dsn));
        }

        return array('host' => $match[3], 'user' => $match[1], 'password' => $match[2], 'port' => $match[4], 'path' => $match[5]);
    }

    public static function createCon($dsn, $sslCertificate = null)
    {
        $details = self::parse($dsn);

        if ($sslCertificate !== null) {
            return new RetrySSLConnection(
                $details['host'], $details['port'], $details['user'], $details['password'], $details['path'],
                array(
                    'capath' => '/etc/ssl/certs',
                    'cafile' => $sslCertificate,
                    'verify_peer' => true,
                ),
                array(
                    'read_write_timeout' => 10,
                    'heartbeat' => 5,
                )
            );
        }

        return new RetryConnection($details['host'], $details['port'], $details['user'], $details['password'], $details['path'], false, 'AMQPLAIN', null, 'en_US', 3.0, 10, null, false, 5);
    }
}