<?php

namespace Scrutinizer\RabbitMQ;

use PhpAmqpLib\Connection\AMQPConnection;
use PhpAmqpLib\Exception\AMQPRuntimeException;

/**
 * This wraps the original connection class to retry the connection if the first attempt fails.
 *
 * It is not particularly elegant as the connection class neither has an interface, and additionally tries to connect
 * directly in the constructor instead of a separate connect method. Furthermore, decorating is a pain as there is
 * neither an interface, nor an abstract base class which we could use. We would have to maintain all public method
 * ourselves which is some work.
 */
class RetryConnection extends AMQPConnection
{
    public function __construct()
    {
        $ref = new \ReflectionClass(get_parent_class());
        $args = func_get_args();

        // First check whether the connection can be established. If that's the case we close the temporary connection
        // again, and call the parent method. This avoids terminating immediately when the RabbitMQ server restarts.
        $attempt = 0;
        while ($attempt < 5) {
            try {
                $con = $ref->newInstanceArgs($args);
                $con->close();

                break;
            } catch (AMQPRuntimeException $ex) {
                if (false === strpos($ex->getMessage(), 'Connection refused')) {
                    throw $ex;
                }

                sleep(pow(2, $attempt));
            }

            $attempt += 1;
        }

        call_user_func_array(array(get_parent_class(), '__construct'), $args);
    }
}