# 发布和订阅（Publish/Subscribe）

我们构建一个日志系统，广播一条消息给多个消费者，多个消费者收到消息后做不同的处理。比如，一个保存日志到硬盘，另一个打印输出到屏幕。

* 交换机（Exchanges）

    在RabbitMQ中生产者（producer）不会直接发送消息给队列（queue），甚至生产者都不知道消息是否会发送给哪个队列。

    生产者只会将消息发送给交换机（exchange），交换机一边接收生产者发送的消息，另一边将消息放到队列里。

    在前边的代码里没有指定exchange为什么队列还能收到消息呢？那是因为我们使用了默认的空字符串''作为exchange，如果有第三个参数routing_key，则routing_key决定将消息放到哪个队列。

    ```
    $channel->basic_publish($message, '', 'hello');
    ```

    交换机（exchange）必须知道收到消息后如何处理，是放到指定的队列，还是放到多个队列，还是丢弃。这个规则由路由类型（exchange type）定义，例如：direct、topic、headers、fanout。

    首先创建一个叫做logs的fanout类型的exchange，fanout的意思是将消息广播给已知的队列。

    ```
    $channel->exchange_declare('logs', 'fanout', false, false, false);
    $channel->basic_publish($msg, 'logs');
    ```

* 临时队列（Temporary queues）

    前面说到了，RabbitMQ只会将消息发送给交换机，然后交换机放到队列，所以我们需要将队列绑定到交换机，然后交换机才会发消息给队列。

    生产者发布消息时，因为发送到交换机，所以并没有声明队列，在消费者代码李我们需要一个新的，空队列，这个队列在消费者断开连接时会自动删除。

    在[php-amqplib](https://github.com/php-amqplib/php-amqplib)的queue_declare方法中，当第一个参数queue为空字符串时，RabbitMQ会生成类似amq.gen-JzTY20BRgKO-HjmUJj0wLg格式的随机队列名称。queue_declare第四个参数exclusive会设置队列为排他性，当连接断开时会自动删除队列。

    然后将队列绑定到交换机，然后交换机才会发消息给队列。

    ```
    list($queue_name, , ) = $channel->queue_declare('', false, false, true, false);

    $channel->queue_bind($queue_name, 'logs');
    ```

* 完整代码

    * [emit_log.php](./emit_log.php)

    * [receive_log.php](./receive_log.php)

* rabbitmqctl命令

    * exchange列表

        ```
        rabbitmqctl list_exchanges
        ```

    * exchange绑定列表

        ```
        rabbitmqctl list_bindings
        ```
