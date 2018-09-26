# 路由（Routing）

RabbitMQ可以将消息广播给许多接收者，如何只发送给指定类型或者一部分接收者呢？比如：日志系统中将所有日志打印输出，而重要的错误日志存放磁盘。

### 绑定（Binding）

在前边的代码里，我们已经绑定过队列，回想下代码：

```
$channel->queue_bind($queue_name, 'logs');
```

绑定是exchange和queue之间的一种关系，可以理解为，queue关注或者对exchange的消息感兴趣，想要接收到exchange的消息。

queue_bind方法可以设置第三个参数routing_key，为了和$channel::basic_publish的第三个参数routing_key做区分，我们称其为binding_key。

```
$binding_key = 'black';
$channel->queue_bind($queue_name, $exchange_name, $binding_key);
```

binding_key的含义根据exchange的不同而改变。当exchange为fanout时，binding_key被忽略。

### Direct exchange

之前我们的日志系统会将所有的消息广播给所有的消费者，但是我们希望程序可以根据队列处理的错误级别去过滤消息，比如：我们只希望将收到的error信息写到磁盘，而不希望info和warning也占用磁盘空间。

我们使用的fanout exchange没有给我们自由操作的空间，它只能无脑地将消息广播给所有绑定的队列。

这里我们使用direct exchange，其背后的路由算法也非常简单，exchange会将消息发送给binding key完全和routing key匹配的queue。

### Multiple binding

多个queue的binding key允许相同

```
$binding_key = 'black';
$channel->queue_bind($queue_one, $exchange_name, $binding_key);
$channel->queue_bind($queue_two, $exchange_name, $binding_key);
```

### 发送日志（Emitting logs）

我们使用direct exchage代替日志系统中的fanout，将日志的严重级别作为binding key，这样接收脚本可以根据严重界别接收消息。

首先我们需要创建exchange：

```
$channel->exchange_declare('direct_logs', 'direct', false, false, false);
```

接下来发送消息：

```
$channel->exchange_declare('direct_logs', 'direct', false, false, false);
$channel->basic_publish($msg, 'direct_logs', $severity);
```

### 订阅消息（Subscribing）

根据队列接收日志的级别创建绑定。

```
foreach ($severities as $severity) {
    $channel->queue_bind($queue_name, 'direct_logs', $severity);
}
```

### 整合代码

[emit_log_direct.php](./emit_log_direct.php)代码：

```
<?php

require_once dirname(__DIR__).'/vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

$connection = new AMQPStreamConnection('127.0.0.1', 5672, 'guest', 'guest');
$channel = $connection->channel();

$channel->exchange_declare('direct_logs', 'direct', false, false, false);

$serverity = isset($argv[1]) && !empty($argv[1]) ? $argv[1] : 'info';

$data = implode(' ', array_slice($argv, 1));
if (empty($data)) {
    $data = 'Hello World!';
}

$messaga = new AMQPMessage($data);

$channel->basic_publish($messaga, 'direct_logs', $serverity);

echo ' [x] Sent ', $severity, ':', $data, "\n";

$channel->close();
$connection->close();
```

[receive_log_direct.php](./receive_log_direct.php)代码：

```
<?php

require_once dirname(__DIR__).'/vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;

$connection = new AMQPStreamConnection('127.0.0.1', 5672, 'guest', 'guest');
$channel = $connection->channel();

$channel->exchange_declare('direct_logs', 'direct', false, false, false);

list($queue_name, , ) = $channel->queue_declare('', false, false, true, false);

$serverities = array_slice($argv, 1);
if (empty($serverities)) {
    file_put_contents('php://stderr', "Usage: $argv[0] [info] [warning] [error]\n");
    exit(1);
}

foreach ($serverities as $serverity) {
    $channel->queue_bind($queue_name, 'direct_logs', $serverity);
}

echo " [*] Waiting for logs. To exit press CTRL+C\n";

$callback = function ($msg) {
    echo ' [x] ', $msg->delivery_info['routing_key'], ':', $msg->body, "\n";
};

$channel->basic_consume($queue_name, '', false, true, false, false, $callback);

while (count($channel->callbacks)) {
    $channel->wait();
}

$channel->close();
$connection->close();
```

保存warning和error日志信息：

```
php receive_logs_direct.php warning error > logs_from_rabbit.log
```

输出所有的日志信息：

```
php receive_logs_direct.php info warning error
```

发送error日志：

```
php emit_log_direct.php error "Run. Run. Or it will explode."
```
