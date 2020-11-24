exchange_declare('direct_logs', 'direct', false, false, false);// 这个是申明交换器，如果没有申明就给默认队列的这个交换器，而且发送的类型默认是direct）
顺序	参数名	默认值	作用
1	$exchange	无	交换机名
2	$type	无	交换机类型，分别有direct、fanout、topic
3	$passsive	false	只判断不创建（一般用于判断该交换机是否存在），如果你希望查询交换机是否存在．而又不想在查询时创建这个交换机．设置此为true即可
如果交换机不存在,则会抛出一个错误的异常.如果存在则返回NULL
4	$durable	false	表示了如果MQ服务器重启,这个交换机是否要重新建立(如果设置为true则重启mq，该交换机还是存在，相当于持久化。)
我们的案例代码有点类似于在服务器设立一个数据库内存表,并且每次访问都要判定内存表是否存在.
而如果开启了这个属性,则相当于建了一个永久表.以后直接访问即可.不需要每次都判定是否存在.如同访问MYSQL。
5	$auto_delete	true	无用自动销毁。如果绑定的所有队列都不在使用了.是否自动删除这个交换机.（比如设置为true,它绑定的对列全部被删除后，该交换器会被自动删除，）
6	$internal	false	内部交换机.即不允许使用客户端推送消息.MQ内部可以让交换机作为一个队列绑定到另外一个交换机下.想想一下以太网的交换机就是了.所以开启这个属性,表示是一个他直接收其他交换机发来的信息
7	$nowait	false	如果为True则表示不等待服务器回执信息.函数将返回NULL,可以提高访问速度..应用范围不确定
8	$arguments	null	额外的一些参数,比如优先级,什么的.需要单独开篇讲
9	$ticket	null	未知
 
 
queue_declare('queueName', false, true, false, true, false)
顺序	参数名	默认值	作用
1	$queue	无	队列名.而存在默认值的意思是.你可以创建一个不重复名称的一个临时队列.(交换机没法创建临时的)
如获得通道后执行如下代码.
2	$passsive	false	只判断不创建(判断该队列是否存在) 只查询不创建.如果为true,如果存在这个队列,则会返回队列的信息.如果不存在这个队列..则会抛异常(与交换机不同的是,如果交换机判断存在,则返回NULL,否则异常)
3	$durable	false	重启重建(持久化)
4	$exclusive	false	排他队列,如果你希望创建一个队列,并且只有你当前这个程序(或进程)进行消费处理.不希望别的客户端读取到这个队列.用这个方法甚好.而同时如果当进程断开连接.这个队列也会被销毁.不管是否设置了持久化或者自动删除.
5	$auto_delete	true	自动销毁（当最后一个消费者取消订阅时队列会自动移除，对于临时队列只有一个消费服务时适用，）
6	$nowait	false	执行后不需要等结果
7	$arguments	null	$arguments = new AMQPTable([
                'x-message-ttl'          => 10000,  // 延迟时间 （毫秒）创建queue时设置该参数可指定消息在该queue中待多久，可根据x-dead-letter-routing-key和x-dead-letter-exchange生成可延迟的死信队列。
                'x-expires'              => 26000,  // 队列存活时间  如果一个队列开始没有设置存活时间，后面又设置是无效的。
                'x-dead-letter-exchange' => 'exchange_direct_ttl3',  // 延迟结束后指向交换机（死信收容交换机）
                'x-dead-letter-queue'    => 'queue_ttl3',  // 延迟结束后指向队列（死信收容队列）,
                //'x-dead-letter-routing-key' => 'queue_ttl3',  // 设置routing-key
                //'x-max-priority'=>'10' //声明优先级队列.表示队列应该支持的最大优先级。建议使用1到10之间.该参数会造成额外的CPU消耗。
            ]
        );
8	$ticket	null	 
 
queue_bind('queue_delete1', 'exchange_delete1');
参数序号	参数名	作用
1	$queue	队列名
2	$exchange	交换机名
3	$routing_key	路由名（对应）
4	$nowait	不等待执行结果
5	$arguments	额外参数
6	$ticket	….
$message = new AMQPMessage("消息内容",['配置项'=>'配置值']);
配置项	类型	说明
content_type	短文本	MIME类型表示消息是一种什么类型的格式,参考MIME类型
content_encoding	短文本	正文传输编码,比如内容是gzip压缩的.值就是gzip,参考
application_headers	数组	请求的headers信息
delivery_mode	数字	表示是否持久化,1为否,2为是 参考
priority	数字	发送权重,也就是优先级
correlation_id	短文本	相关性ID 参考
reply_to	短文本	消息被发送者处理完后,返回回复时执行的回调(在rpc时会用到)
expiration	短文本	存活时间,毫秒数
message_id	短文本	扩展属性
timestamp	数字	时间戳
type	短文本	扩展属性
user_id	短文本	扩展属性
app_id	短文本	扩展属性
cluster_id	短文本	扩展属性
Basic_publish($msg, $exchange, $routing_key)
顺序	参数名	作用
1	$msg	消息对象
2	$exchange	消息对象(交换机名称) 如果没有指定交换器，会指定一个默认的交换器，第三个参数是路由键，当申明一个队列时，它会自动绑定到默认交换器，并以队列名称作为路由键。那么这段代码会自动发送到hello的队列    （hello队列必须事先申明好）。所以这种消息，当有多个进程时会均匀的分给不同的进程处理
3	$routing_key	消息的路由名
4	$mandatory	消息至少有一个队列能够接受,如果交换机无法把消息发送到具体的队列中,是否要把消息发送到失败投递记录中,而不是让其消失（当mandatory标志位设置为true时，如果exchange根据自身类型和消息routingKey无法找到一个合适的queue存储消息，那么broker会调用basic.return方法将消息返还给生产者;当mandatory设置为false时，出现上述情况broker会直接将消息丢弃;通俗的讲，mandatory标志告诉broker代理服务器至少将消息route到一个队列中，否则就将消息return给发送者;）
5	$immediate	这个是一个被作废的属性.
6	$ticket	 
 
function 接收消息回调($message)
{
    $message->delivery_info['channel']->basic_ack($message->delivery_info['delivery_tag']);
}
代码的意思为,根据消息的delivery_info['channel']找到通道,并调用通道的basic_ack方法发送消息的确认内容.
##泄露问题
如果我们只是接受了消息,并进行处理.但是处理完后.没有发起ack就会导致服务器上的消息一直堆积.服务器会发送新的消息.同时会记录当前的这个链接有哪些消息一直还没回复.(服务器认为你会回复,一直等待)。如果消费者进程停止掉重启..就会重新接收所有消息！
 
$channel->basic_qos(null, 1, null);
可告知RabbitMQ只有在consumer处理并确认了上一个message后才分配新的message给他，否则上一个没处理完会一直卡在这里，这个根据业务场景配置，
basic_qos注意事项：由于消费者自身处理能力有限，从rabbitmq获取一定数量的消息后，希望rabbitmq不再将队列中的消息推送过来，当对消息处理完后（即对消息进行了ack，并且有能力处理更多的消息）再接收来自队列的消息
这时候我们就需要要到basic_qos，
basic_qos($prefetch_size,
 $prefetch_count, //最重要的参数，未确认的消息同时存在的个数;（也就是未ack的消息数，我们可以以此来作为记录失败数据的个数；）
$a_global
)
prefetch_count在no_ask=false的情况下生效，即在自动应答的情况下这两个值是不生效的
注意：如果我们有两个进程，一个设置prefetch_count为1，一个没有设置这个，这样只会有一个进程会等待确认，还有一个不会等待确认，这样容易导致不可预知的错误；当多个进程设置prefetch_count值时，相互之间的数据时没有影响的；比如两个进程都设置的是2那么总的未确认存在数是4；
 
 
basic_consume("TestQueue", "", false, false, false, false, $callback)
顺序	参数名	默认值	作用
1	queue	 	消息要取得消息的队列名
2	consumer_tag	 	消费者标签
3	no_local	false	这个功能属于AMQP的标准,但是rabbitMQ并没有做实现.
4	no_ack	false	收到消息后,是否不需要回复确认即被认为被消费（在默认情况下，消息确认机制是关闭的。现在是时候开启消息确认机制，该参数设置为true,并且工作进程处理完消息后发送确认消息。）
5	exclusive	false	排他消费者,即这个队列只能由一个消费者消费.适用于任务不允许进行并发处理的情况下.比如系统对接
6	nowait	false	不返回执行结果,但是如果排他开启的话,则必须需要等待结果的,如果两个一起开就会报错
7	callback	null	回调函数
8	ticket	null	 
9	arguments	null	 
