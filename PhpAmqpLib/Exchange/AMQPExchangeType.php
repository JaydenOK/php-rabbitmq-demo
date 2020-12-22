<?php
namespace PhpAmqpLib\Exchange;

final class AMQPExchangeType
{
    const DIRECT = 'direct';
    const FANOUT = 'fanout';
    const TOPIC = 'topic';
    const HEADERS = 'headers';
    const X_DELAYED_MESSAGE = 'x-delayed-message';
}
