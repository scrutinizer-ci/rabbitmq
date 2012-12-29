<?php

namespace Scrutinizer\RabbitMQ\Util;

abstract class DsnUtils
{
    public static function parse($dsn)
    {
        if ( ! preg_match('#^([^:]+):([^@]+)@([^:]+):([0-9]+)(/.*)$#', $dsn, $match)) {
            throw new \InvalidArgumentException(sprintf('Could not parse DSN "%s".', $dsn));
        }

        return array('host' => $match[3], 'user' => $match[1], 'password' => $match[2], 'port' => $match[4], 'path' => $match[5]);
    }
}