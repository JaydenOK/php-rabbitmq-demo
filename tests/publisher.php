<?php

require 'bootstrap.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exchange\AMQPExchangeType;
use PhpAmqpLib\Message\AMQPMessage;

$exchange = 'test_amqplib';
$queue = 'test_amqplib_queue_1';

$connection = new AMQPStreamConnection(HOST, PORT, USER, PASS, VHOST);
$channel = $connection->channel();

/*
    The following code is the same both in the consumer and the producer.
    In this way we are sure we always have a queue to consume from and an
        exchange where to publish messages.
*/

/**
 * $queue 队列名.而存在默认值的意思是.你可以创建一个不重复名称的一个临时队列
 * $passive false 被动状态 不存在是否（不）创建，false 即原来不存在此队列，则创建
 * $durable    false  是否持久化到硬盘，如果是，MQ服务器重启，数据依然存在
 * $exclusive 排他队列,如果你希望创建一个队列,并且只有你当前这个程序(或进程)进行消费处理.不希望别的客户端读取到这个队列.用这个方法甚好.而同时如果当进程断开连接.这个队列也会被销毁.不管是否设置了持久化或者自动删除.
 * $auto_delete 自动销毁（当最后一个消费者取消订阅时队列会自动移除，对于临时队列只有一个消费服务时适用，）
 */
$channel->queue_declare($queue, false, true, false, false);

/**
 * $exchange 交换机名称
 * $type  交换机类型，有direct、fanout、topic
 * $passive false 被动状态 不存在是否（不）创建，即false会创建
 * $durable    false  是否持久化到硬盘，如果是，MQ服务器重启，数据依然存在
 * $auto_delete 此交换机没有绑定的队列时，是否自动删除
 */
$channel->exchange_declare($exchange, AMQPExchangeType::DIRECT, false, true, false);

//$channel->queue_bind($queue, $exchange);

$messageBody = implode(' ', array_slice($argv, 1));
if (empty($messageBody)) {
    $messageBody = 'default message';
}

$message = new AMQPMessage($messageBody, array('content_type' => 'text/plain', 'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT));
$channel->basic_publish($message, $exchange);

$channel->close();
$connection->close();

echo 'publish finish';