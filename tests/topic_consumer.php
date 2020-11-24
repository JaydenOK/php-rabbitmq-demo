<?php

require 'bootstrap.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

$exchange = 'test_amqplib_topic';
$queue = 'test_amqplib_queue_topic_1';

$connection = new AMQPStreamConnection(HOST, PORT, USER, PASS);
$channel = $connection->channel();

$channel->exchange_declare($exchange, 'topic', false, true, false);
/**
 * $exclusive 排他队列,如果你希望创建一个队列,并且只有你当前这个程序(或进程)进行消费处理.不希望别的客户端读取到这个队列.用这个方法甚好.
 * 而同时如果当进程断开连接.这个队列也会被销毁.不管是否设置了持久化或者自动删除.
 */
list($queue_name, ,) = $channel->queue_declare($queue, false, true, false, false);

$binding_keys = array_slice($argv, 1);
if (empty($binding_keys)) {
    file_put_contents('php://stderr', "Usage: $argv[0] [binding_key]\n");
    exit(1);
}

foreach ($binding_keys as $binding_key) {
    $channel->queue_bind($queue_name, $exchange, $binding_key);
}

echo " [*] Waiting for logs. To exit press CTRL+C\n";

/**
 * basic_qos注意事项：由于消费者自身处理能力有限，从rabbitmq获取一定数量的消息后，希望rabbitmq不再将队列中的消息推送过来，
 * 当对消息处理完后（即对消息进行了ack，并且有能力处理更多的消息）再接收来自队列的消息,这时候我们就需要要到basic_qos，
 * basic_qos($prefetch_size, $prefetch_count, $a_global)
 * $prefetch_count, //最重要的参数，未确认的消息同时存在的个数;（也就是未ack的消息数，我们可以以此来作为记录失败数据的个数；）
 * prefetch_count在no_ask=false的情况下生效，即在自动应答的情况下这两个值是不生效的
 * 注意：如果我们有两个进程，一个设置prefetch_count为1，一个没有设置这个，这样只会有一个进程会等待确认，还有一个不会等待确认，这样容易导致不可预知的错误；
 * 当多个进程设置prefetch_count值时，相互之间的数据时没有影响的；比如两个进程都设置的是2那么总的未确认存在数是4
 */
$channel->basic_qos(null, 1, null);

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
$channel->basic_consume($queue_name, '', false, false, false, false, function (AMQPMessage $message) {
    echo "\n[receive data]:\n" . $message->body;
    if ($message->body == 'ok') {
        $message->ack();
    } else {
        $message->nack();
    }
    // Send a message with the string "quit" to cancel the consumer.
    if ($message->body === 'quit') {
        $message->getChannel()->basic_cancel($message->getConsumerTag());
    }
});

while ($channel->is_consuming()) {
    $channel->wait();
}

$channel->close();
$connection->close();