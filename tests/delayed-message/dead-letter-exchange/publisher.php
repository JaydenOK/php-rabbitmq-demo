<?php

require '../../bootstrap.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;

$orderExchange = 'order-exchange';  //发布者投递到此交换机的队列
$orderQueue = 'order-queue';
$orderDeadLetterExchange = 'order-dead-letter-exchange';   // 延迟交换机 : Dead-Letter-Exchange
$orderDeadLetterQueue = 'order-dead-letter-queue';   // 消费队列(order-queue消息到期后，投递到此队列)

$connection = new AMQPStreamConnection(HOST, PORT, USER, PASS);
$channel = $connection->channel();

//声明2个交换机
$channel->exchange_declare($orderDeadLetterExchange, 'direct', false, true, false);
$channel->exchange_declare($orderExchange, 'direct', false, true, false);

//如果同时设置队列消息过期时间和投递消息过期时间，那么以过期时间小的那个数值为准。
$queueDelayTime = 10;
//配置order-exchange属性，设置x-dead-letter-exchange，x-dead-letter-routing-key为到过期时间后，消息投放的交换机、路由，x-message-ttl设置时间
$table = new AMQPTable();
$table->set('x-dead-letter-exchange', $orderDeadLetterExchange);
$table->set('x-dead-letter-routing-key', $orderDeadLetterExchange);
$table->set('x-message-ttl', $queueDelayTime * 1000);

//================================= 主要区别
//正常声明订单队列，加绑定$table配置
$channel->queue_declare($orderQueue, false, true, false, false, false, $table);
//正常绑定队列
$channel->queue_bind($orderQueue, $orderExchange, $orderExchange);

//正常声明死信队列，绑定死信队列
$channel->queue_declare($orderDeadLetterQueue, false, true, false, false, false);
$channel->queue_bind($orderDeadLetterQueue, $orderDeadLetterExchange, $orderDeadLetterExchange);

/////////////////////////////////////////////////////////////////////////////////////////////
//发送消息

$y = 0;
for ($y = 0; $y < 6; $y++) {
    //延迟秒数
    $second = pow(2, $y);
    $data = ['code' => 0, 'message' => 'ok', 'data' => ['second' => $second, 'rand' => rand()]];
    $dataString = json_encode($data, JSON_UNESCAPED_UNICODE);
    $message = new AMQPMessage($dataString,
        [
            'expiration' => intval($second),
            'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
        ]
    );
    $channel->basic_publish($message, $orderExchange, $orderExchange);
    echo date('[Y-m-d H:i:s]:') . $dataString . PHP_EOL;
}
