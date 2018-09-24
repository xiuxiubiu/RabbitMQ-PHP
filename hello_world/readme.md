# 基本的消息发布和消费操作（[Hello World](http://www.rabbitmq.com/tutorials/tutorial-one-php.html)）

* 向队列发送消息

    * 引入autoload、创建连接、创建channel。

        ```
        require_once __DIR__ . '/vendor/autoload.php';

        use PhpAmqpLib\Connection\AMQPStreamConnection;
        use PhpAmqpLib\Message\AMQPMessage;

        $connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');
        $channel = $connection->channel();
        ```

        channel提供了大多数的API操作方法。

    * 声明消息发布队列。

        ```
        $channel->queue_declare('hello', false, false, false);
        ```

        queue_declare操作是幂等性的，声明多次和一次都只会在队列不存在时才创建队列。

    * 发布消息

        ```
        $message = new AMQPMessage('Hello World!');
        $channel->basic_publish($message, '', 'hello');
        ```

    * 关闭通道和连接
        ```
        $channel->close();
        $connection->close();
        ```

    * [send.php 完整代码](./send.php)

* 从接收消息

    * 引入autoload、创建连接、创建channel。

        ```
        require_once __DIR__ . '/vendor/autoload.php';

        use PhpAmqpLib\Connection\AMQPStreamConnection;

        $connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');
        $channel = $connection->channel();
        ```

    * 声明要消费的队列

        ```
        $channel->queue_declare('hello', false, false, false);
        ```
        这里声明消费队列是正确的，因为不能保证消费操作一定在发送操作后才执行，所以声明消费队列保证消费时队列存在。

    * 处理接收到的消息

        ```
        $callback = function ($message) {
            echo ' [x] Received ', $message->body, "\n";
        };

        $channel->basic_consume('hello', '', false, true, false, false, $callback);
        ```

    * [receive.php 完整代码](./receive.php)


* 总结

    * 发布消息和消费消息时都应该声明队列。

    *  因为queue_declare是幂等性的，并且发布和消费都要保证队列存在。

    *  注意同一个发布和消费，声明队列时参数保持一致。
