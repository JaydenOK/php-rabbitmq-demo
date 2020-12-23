<?php

/**
 * Dead Letter Exchange其实就是一种普通的exchange，和创建其他exchange没有两样。只是在某一个设置Dead Letter Exchange的队列中有消息过期了，
 * 会自动触发消息的转发，发送到Dead Letter Exchange中去。
 * 消费者，监听Dead Letter Queue进行消费
 */

use PhpAmqpLib\Connection\AMQPStreamConnection;

require '../../bootstrap.php';

$orderExchange = 'order-exchange';  //发布者投递到此交换机的队列
$orderQueue = 'order-queue';
$orderDeadLetterExchange = 'order-dead-letter-exchange';   // 延迟交换机 : Dead-Letter-Exchange
$orderDeadLetterQueue = 'order-dead-letter-queue';   // 消费队列(order-queue消息到期后，投递到此队列)

$connection = new AMQPStreamConnection(HOST, PORT, USER, PASS);
$channel = $connection->channel();

//重新设置完参数，如果参数不一致，但交换机已经存在rabbitMQ,会报参数不一致的异常(需删除rabbitMQ交换机，让它重新生成)
//$passive 参数false,交换机不存在时会创建，true则不存在报异常
//$durable	false	表示了如果MQ服务器重启,这个交换机是否要重新建立(如果设置为true则重启mq，该交换机还是存在，相当于持久化。)
$channel->exchange_declare($orderDeadLetterExchange, 'direct', false, true, false);
$channel->exchange_declare($orderExchange, 'direct', false, true, false);

//正常声明绑定死信队列
$channel->queue_declare($orderDeadLetterQueue, false, true, false, false, false);
$channel->queue_bind($orderDeadLetterQueue, $orderDeadLetterExchange, $orderDeadLetterExchange);

echo ' [*] Waiting for message. To exit press CTRL+C ' . PHP_EOL;

$callback = function ($msg) {
    echo date('Y-m-d H:i:s') . " [x] Received", $msg->body, PHP_EOL;

    $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);

};

//只有consumer已经处理并确认了上一条message时queue才分派新的message给它
$channel->basic_qos(null, 1, null);
$channel->basic_consume($orderDeadLetterQueue, '', false, false, false, false, $callback);


while (count($channel->callbacks)) {
    $channel->wait();
}
$channel->close();
$connection->close();