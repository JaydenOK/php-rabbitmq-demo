<?php
/**
 * 使用x-delayed-message 插件实现消息延迟投递
 */

/**
 * 构建消费端消费端改变不大，交换机声明处同生产者保持一样，设置交换机类型（x-delayed-message）和 x-delayed-type
 */

use PhpAmqpLib\Connection\AbstractConnection;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exchange\AMQPExchangeType;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;

require '../../bootstrap.php';

$exchange = 'test_amqplib';
$queue = 'test_amqplib_queue_1';
$consumerTag = 'test_amqplib_tag_2';


$connection = new AMQPStreamConnection(HOST, PORT, USER, PASS);
$channel = $connection->channel();

$channel->exchange_declare($exchange, AMQPExchangeType::X_DELAYED_MESSAGE, false, true, false, false, false,
    new AMQPTable(["x-delayed-type" => AMQPExchangeType::FANOUT])
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
$channel->queue_declare($queue, false, false, false, false, false,
    new AMQPTable(["x-dead-letter-exchange" => "delayed"])
);

$channel->queue_bind($queue, $exchange);

///////////////////////////////////////////////////////////////////////////////////////////////////
/// ///////////////////////////////////////////////////////////////////////////////////////////////


echo "\n  bound {$queue}:{$exchange}, consume... \n";
/*
    queue: Queue from where to get the messages
    consumer_tag: Consumer identifier
    no_local: Don't receive messages published by this consumer.
    no_ack: If set to true, automatic acknowledgement mode will be used by this consumer. See https://www.rabbitmq.com/confirms.html for details.
    exclusive: Request exclusive consumer access, meaning only this consumer can access the queue
    nowait:
    callback: A PHP Callback
*/
/**
 * $queue 消费队列
 * 1    queue         消息要取得消息的队列名
 * 2    consumer_tag         消费者标签
 * 3    no_local    false    这个功能属于AMQP的标准,但是rabbitMQ并没有做实现.
 * 4    no_ack    false    收到消息后,是否不需要回复确认即被认为被消费（在默认情况下，消息确认机制是关闭的。现在是时候开启消息确认机制，该参数设置为true,并且工作进程处理完消息后发送确认消息。）
 * 5    exclusive    false    排他消费者,即这个队列只能由一个消费者消费.适用于任务不允许进行并发处理的情况下.比如系统对接
 * 6    nowait    false    不返回执行结果,结合exclusive，但是如果排他开启的话, 则必须需要等待结果的,如果两个一起开就会报错
 * 7    callback    null    回调函数
 * 8    ticket    null     
 * 9    arguments    null
 */
$channel->basic_consume($queue, $consumerTag, false, false, false, false, function (AMQPMessage $message) {
    echo "\n[receive data]:\n" . $message->body;
    $data = json_decode($message->body, true);
    if ($data['code'] === 0) {
        $message->ack();
    } else {
        echo "\n nack \n";
        $message->nack(true);
    }
    // Send a message with the string "quit" to cancel the consumer.
    if ($data['code'] === '-1') {
        $message->getChannel()->basic_cancel($message->getConsumerTag());
    }
    sleep(1);
});


function shutdown($channel, $connection)
{
    $channel->close();
    $connection->close();
}

register_shutdown_function('shutdown', $channel, $connection);

// Loop as long as the channel has callbacks registered
while ($channel->is_consuming()) {
    $channel->wait();
}


echo 'ok';