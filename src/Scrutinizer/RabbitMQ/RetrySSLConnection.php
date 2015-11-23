<?php

namespace Scrutinizer\RabbitMQ;

use PhpAmqpLib\Connection\AMQPConnection;
use PhpAmqpLib\Connection\AMQPLazyConnection;
use PhpAmqpLib\Connection\AMQPSSLConnection;
use PhpAmqpLib\Exception\AMQPRuntimeException;

/**
 * This wraps the original connection class to retry the connection if the first attempt fails.
 *
 * It is not particularly elegant as the connection class neither has an interface, and additionally tries to connect
 * directly in the constructor instead of a separate connect method. Furthermore, decorating is a pain as there is
 * neither an interface, nor an abstract base class which we could use. We would have to maintain all public method
 * ourselves which is some work.
 */
class RetrySSLConnection extends AMQPSSLConnection
{
    protected function connect()
    {
        $firstException = null;

        $attempt = 0;
        while ($attempt < 5) {
            try {
                parent::connect();

                return;
            } catch (AMQPRuntimeException $ex) {
                if ($firstException === null) {
                    $firstException = $ex;
                }

                if (false === strpos($ex->getMessage(), 'Connection refused') && false === strpos($ex->getMessage(), 'Connection timed out')) {
                    throw $ex;
                }

                sleep(pow(2, $attempt));
            }

            $attempt += 1;
        }

        throw $firstException;
    }
}