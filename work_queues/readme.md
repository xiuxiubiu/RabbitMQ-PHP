# 工作队列（[Work Queues](http://www.rabbitmq.com/tutorials/tutorial-two-php.html)）

* 准备工作

    我们没有真实的工作任务，因此用sleep()函数模拟耗时任务操作，接收到的消息中，每个“.”代表一秒任务执行耗时。例如：Hello...表示完成任务操作需要3秒。

    * 发布任务消息

        在[send.php](../hello_world/send.php)的基础上做修改，新文件命名为new_task.php。

        ```
        $data = implode(' ', array_slice($argv, 1));
        if (empty($data)) {
            $data = "Hello World!";
        }
        $msg = new AMQPMessage($data);

        $channel->basic_publish($msg, '', 'hello');

        echo ' [x] Sent ', $data, "\n";
        ```

    * 消费任务消息

        在[receive.php](../hello_world/receive.php)的基础上模拟耗时任务，命名为worker.php。

        ```
        $callback = function ($message) {
            echo ' [x] Received ', $message->body, "\n";
            sleep(substr_count($message->body, '.'));
            echo " [x] Done\n";
        };

        $channel->basic_consume('hello', '', false, true, false, false, $callback);
        ```

* 轮询调度（Round-robin dispatching）

    开启两个控制台，运行worker.php，消费队列任务。

    ```
    # shell 1
    php worker.php
    # => [*] Waiting for messages. To exit press CTRL+C
    ```

    ```
    # shell 2
    php worker.php
    # => [*] Waiting for messages. To exit press CTRL+C
    ```

    再开启一个控制台，运行new_task.php，发布任务消息。

    ```
    # shell 3
    php new_task.php First message.
    php new_task.php Second message..
    php new_task.php Third message...
    php new_task.php Fourth message....
    php new_task.php Fifth message.....
    ```

    运行结果

    ```
    # shell 1
    php worker.php
    # => [*] Waiting for messages. To exit press CTRL+C
    # => [x] Received 'First message.'
    # => [x] Received 'Third message...'
    # => [x] Received 'Fifth message.....'
    ```

    ```
    # shell 2
    php worker.php
    # => [*] Waiting for messages. To exit press CTRL+C
    # => [x] Received 'Second message..'
    # => [x] Received 'Fourth message....'
    ```

    <div id="round-robin-default"></div>
    默认RabbitMQ会一次性地平均地将消息发送到下个消费者，每个消费者都会得到相同数量的消息。

* 消息确认（Message acknowledgment）

    当前代码，RabbitMQ发送消息给消费者后会立马删除消息，如果杀死正在执行任务的消费者，消息就会丢失。

    为了保证消息不会丢失，RabbitMQ支持Message acknowledgment，消费者发送确认消息给RabbitMQ，告诉它消息已经被接收、处理，可以删除消息了。如果消费者被杀死没有发送确认消息，RabbitMQ会将消息重新转发给别的消费者。

    Message acknowledgment没有超时限制，所以执行非常耗时的操作是没问题的。

    Message acknowledgment默认是关闭的，开启只需要设置basic_consume的第四个参数no_ack为false。

    ```
    $callback = function ($message) {
        echo ' [x] Received ', $message->body, "\n";
        sleep(substr_count($message->body, '.'));
        echo " [x] Done\n";
        $message->delivery_info['channel']->basic_ack($message->delivery_info['delivery_tag']);
    };

    $channel->basic_consume('hello', '', false, false, false, false, $callback);
    ```

* 消息持久化（Message durability）

    当RabbitMQ退出，在我们不设置持久化的情况下，队列和消息都会丢失。为了保证消息不会丢失，我们需要做两个操作：

    * 队列持久化

        queue_declare声明队列时设置第三个参数durable为true

        ```
        $channel->queue_declare('hello', false, true, false);
        ```

    * 消息持久化

        设置AMQPMessage的第二个参数properties中的delivery_mode为2。

        ```
        $properties = [
            'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
        ];
        $message = new AMQPMessage($data, $properties);
        ```

* 公平调度（Fair dispatch）

    假如在轮询调度（Round-robin dispatching）机制下，有两个消费者，基数任务特别耗时，偶数任务很轻松，RabbitMQ不在乎消费者任务是否积压，还是会固定将奇数任务分配给一个消费者，偶数任务分配给另一个消费者。

    为了解决这个问题，可以在消费者代码中使用basic_qos方法，设置第二个参数prefetch_count的值为1，此参数的意思是，如果有prefetch_count个消息没有ack，则不会收到新的消息。设置为1告诉RabbitMQ不要同一时间分配超过一个消息给消费者，这样只有在消费者空闲时才会分配下一条消息给消费者。（[还记得轮询调度的介绍吗？](#round-robin-default)）

    basic_qos只有在需要消息确认，即设置basic_consume的第4个参数no_ack为false时生效。

    ```
    $channel->basic_qos(null, 1, null);
    ```

* 完整代码

    * [new_task.php](./new_task.php)

    * [worker.php](./worker.php)
