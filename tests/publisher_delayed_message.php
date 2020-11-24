<?php

require 'bootstrap.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exchange\AMQPExchangeType;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;

$exchange = 'test_amqplib';
$queue = 'test_amqplib_queue_1';

$connection = new AMQPStreamConnection(HOST, PORT, USER, PASS);
$channel = $connection->channel();

/**
 * Declares exchange
 * 需服务端安装延时插件 x-delayed-message https://www.rabbitmq.com/community-plugins.html
 * @param string $exchange
 * @param string $type
 * @param bool $passive
 * @param bool $durable
 * @param bool $auto_delete
 * @param bool $internal
 * @param bool $nowait
 * @return mixed|null
 */
$channel->exchange_declare($exchange, 'x-delayed-message', false, true, false, false, false,
    new AMQPTable(array("x-delayed-type" => AMQPExchangeType::FANOUT))
);

/**
 * Declares queue, creates if needed
 *
 * @param string $queue
 * @param bool $passive
 * @param bool $durable
 * @param bool $exclusive
 * @param bool $auto_delete
 * @param bool $nowait
 * @param null $arguments
 * @param null $ticket
 * @return mixed|null
 */
$channel->queue_declare($queue, false, true, false, false, false,
    new AMQPTable(array("x-dead-letter-exchange" => "delayed"))
);

$channel->queue_bind($queue, $exchange);

$data = 'hello';

$message = new AMQPMessage($data, array('delivery_mode' => 2));
$headers = new AMQPTable(array("x-delay" => 7000));
$message->set('application_headers', $headers);
$channel->basic_publish($message, $exchange);

//function process_message(AMQPMessage $message)
//{
//    $headers = $message->get('application_headers');
//    $nativeData = $headers->getNativeData();
//    var_dump($nativeData['x-delay']);
//    $message->ack();
//}

//
///*
//    queue: Queue from where to get the messages
//    consumer_tag: Consumer identifier
//    no_local: Don't receive messages published by this consumer.
//    no_ack: If set to true, automatic acknowledgement mode will be used by this consumer. See https://www.rabbitmq.com/confirms.html for details.
//    exclusive: Request exclusive consumer access, meaning only this consumer can access the queue
//    nowait:
//    callback: A PHP Callback
//*/
//
//$channel->basic_consume('delayed_queue', '', false, false, false, false, 'process_message');
//
///**
// * @param \PhpAmqpLib\Channel\AMQPChannel $channel
// * @param \PhpAmqpLib\Connection\AbstractConnection $connection
// */
//function shutdown($channel, $connection)
//{
//    $channel->close();
//    $connection->close();
//}
//
//register_shutdown_function('shutdown', $channel, $connection);
//
//// Loop as long as the channel has callbacks registered
//while ($channel->is_consuming()) {
//    $channel->wait();
//}
