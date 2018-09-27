### 优先级队列

RabbitMQ 3.5.0以后任何queue都可以声明优先级。

### 声明优先级队列

queue_declare声明队列时，设置最后一个参数arguments的x-max-priority属性来声明队列的最大优先级，此参数为1～255之间的正整数，推荐使用1～10之间的证书。

```
$args = new AMQPTable(['x-max-priority'=>10]);
$channel->queue_declare('priority_queue', false, false, false, false, false, $args);
```

### 发送消息的优先级

发送消息时通过设置AMQPMessage第二个参数properties的priority属性设置消息的优先级：

```
$properties = ['priority'=>5];
$message = new AMQPMessage($data, $properties);
```

当priority参数为空时当作0处理，当超过队列的x-max-priority值时，按最大优先级处理。比如：x-max-priority为10，priority设置为20，实际按10处理。


### 完整代码

[priority_send.php](./priority_send.php)

我们执行程序priority_send.php时接收一个参数作为消息的优先级。

```
$properties = isset($argv[1]) && !empty($argv[1]) ? ['priority'=>(int)$argv[1]] : [];
$message = new AMQPMessage($data, $properties);
```

[priority_receive.php](./priority_receive.php)

消费者收到消息后sleep(5)模拟耗时操作，好让消息有时间进行优先级排序。

```
$callback = function ($message) {
    echo ' [x] Received ', $message->body, "\n";
    sleep(5);
    $message->delivery_info['channel']->basic_ack($message->delivery_info['delivery_tag']);
    echo " [x] Done\n";
};
```

发送消息：
```
php priority_queue/priority_send.php

php priority_queue/priority_send.php

php priority_queue/priority_send.php 6

php priority_queue/priority_send.php 10

php priority_queue/priority_send.php 200
```

接收消息：

```
php priority_queue/priority_receive.php
```
