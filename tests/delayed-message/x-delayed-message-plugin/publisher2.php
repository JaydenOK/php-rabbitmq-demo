<?php

require '../../bootstrap.php';

use PhpAmqpLib\Connection\AbstractConnection;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exchange\AMQPExchangeType;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;

$exchange = 'x-delayed-message-exchange';
$queue = 'x-delayed-message-queue';
$consumerTag = 'x-delayed-message-tag';

$connection = new AMQPStreamConnection(HOST, PORT, USER, PASS);
$channel = $connection->channel();

/**
 * Declares exchange
 * 需服务端安装延时插件 x-delayed-message https://www.rabbitmq.com/community-plugins.html
 * CentOS下载.ez文件 https://github.com/rabbitmq/rabbitmq-delayed-message-exchange/releases/download/v3.8.0/rabbitmq_delayed_message_exchange-3.8.0.ez
 * 放置安装扩展目录下：/usr/lib/rabbitmq/lib/rabbitmq_server-3.8.9/plugins/rabbitmq_delayed_message_exchange-3.8.0.ez
 * 启用延时插件: rabbitmq-plugins enable rabbitmq_delayed_message_exchange
 * 完成  ---  到管理后台Exchange界面 查看type,存在x-delayed-message类型即可
 *查看配置文件： /etc/rabbitmq/rabbitmq.conf
 * 不存在，去github官方或网上下载配置文件，修改 端口，复制到 /etc/rabbitmq/rabbitmq.conf
 * ######## 连接监听端口
 * listeners.tcp.default = 6666
 * 重启: systemctl restart rabbitmq-server.service
 */
$channel->exchange_declare($exchange, AMQPExchangeType::X_DELAYED_MESSAGE, false, true, false, false, false,
    new AMQPTable(["x-delayed-type" => AMQPExchangeType::DIRECT])
);

$channel->queue_declare($queue, false, true, false, false, false,
    new AMQPTable(["x-dead-letter-exchange" => $exchange])
);

$channel->queue_bind($queue, $exchange);

//构建生产者
//几个注意点：
//交换机类型一定要设置为 x-delayed-message
//设置 x-delayed-type 为 direct，当然也可以是 topic 等
//发送消息时设置消息头 headers 的 x-delay 属性，即延迟时间，如果不设置消息将会立即投递

///////////////////////////////////////////////////////////////////////////////////////////////////
/// ///////////////////////////////////////////////////////////////////////////////////////////////

$y = 0;
for ($y = 0; $y < 6; $y++) {
    //延迟秒数
    $second = pow(2, $y);
    $data = ['code' => 0, 'message' => 'ok', 'data' => ['second' => $second, 'rand' => rand()]];
    $dataString = json_encode($data, JSON_UNESCAPED_UNICODE);
    $message = new AMQPMessage($dataString, ['delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT]);
    $message->set('application_headers', new AMQPTable(["x-delay" => $second * 1000]));
    $channel->basic_publish($message, $exchange);
    echo date('[Y-m-d H:i:s]:') . $dataString . PHP_EOL;
}


function shutdown($channel, $connection)
{
    $channel->close();
    $connection->close();
}

register_shutdown_function('shutdown', $channel, $connection);