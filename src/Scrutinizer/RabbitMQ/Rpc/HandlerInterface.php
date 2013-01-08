<?php

namespace Scrutinizer\RabbitMQ\Rpc;

interface HandlerInterface
{
    /**
     * Returns the payload type.
     *
     * Any string that is understood by JMSSerializer is permissible.
     *
     * @see http://jmsyst.com/libs/serializer/master/reference/annotations#type
     *
     * @return string
     */
    public function getPayloadType();

    /**
     * Returns the callback for this serializer.
     *
     * Most likely a method on this class.
     *
     * @return callable
     */
    public function getCallback();
}