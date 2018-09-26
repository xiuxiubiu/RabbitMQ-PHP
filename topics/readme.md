# Topic Exchange

在日志系统中，我们希望不仅仅根据日志级别订阅消息，还可以根据日志来源。比如unix中的syslog会根据日志级别（info/warn/crit...）和类型（auth/cron/kern...）来处理日志。

我们可能想即监听来自cron的crit日志，也想监听kern的所有日志。为了完成这个功能我们需要用到topic exchange。

### Topic exchange

消息发送到topic exchange不能使用随意的routing key，必须是一个由.分隔的单词列表。单词列表可以使用任何单词，但是通常它们会说明消息的特征。一些有效的routing key示例："stock.usd.nyse", "nyse.vmw", "quick.orange.rabbit"。routing key可以由很多单词组成，最多不超过255字节。

binding key必须是相同的形式。topic exchange背后的逻辑和direct exchange很相似，同样是将指定了routing key的消息发送给routing key和binding key匹配的queue。但是topic exchange有两种特殊情况：

\* 只能匹配一个单词

\# 可以匹配0个或多个单词

如果发送消息时的routing key在消费端没有binding key和其匹配，那么消息将被抛弃。

topic exchange时非常强大的，可以实现和其他exchange一样的功能。

* 当biding key为\#时，queue将会收到所有的消息，和fanout exchange一样。

* 当binding key不使用\#和\*时，会和direct exchange一样发送消息到binding key和routing key完全匹配的queue。

### 整合代码

假设日志系统中的routing key有两个关键字："<类型>.<级别>"。

[emit_log_topic.php](./emit_log_topic.php)代码：

```
<?php

require_once __DIR__ . '/vendor/autoload.php';
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

$connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');
$channel = $connection->channel();

$channel->exchange_declare('topic_logs', 'topic', false, false, false);

$routing_key = isset($argv[1]) && !empty($argv[1]) ? $argv[1] : 'anonymous.info';
$data = implode(' ', array_slice($argv, 2));
if (empty($data)) {
    $data = "Hello World!";
}

$msg = new AMQPMessage($data);

$channel->basic_publish($msg, 'topic_logs', $routing_key);

echo ' [x] Sent ', $routing_key, ':', $data, "\n";

$channel->close();
$connection->close();
```

[receive_log_topic.php](./receive_log_topic.php)代码：

```
<?php

require_once __DIR__ . '/vendor/autoload.php';
use PhpAmqpLib\Connection\AMQPStreamConnection;

$connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');
$channel = $connection->channel();

$channel->exchange_declare('topic_logs', 'topic', false, false, false);

list($queue_name, ,) = $channel->queue_declare("", false, false, true, false);

$binding_keys = array_slice($argv, 1);
if (empty($binding_keys)) {
    file_put_contents('php://stderr', "Usage: $argv[0] [binding_key]\n");
    exit(1);
}

foreach ($binding_keys as $binding_key) {
    $channel->queue_bind($queue_name, 'topic_logs', $binding_key);
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

接收所有日志：

```
php receive_logs_topic.php "#"
```

接收kern类型的所有日志：

```
php receive_logs_topic.php "kern.*"
```

接收所有critical级别的日志：

```
php receive_logs_topic.php "*.critical"
```

多个binding key绑定

```
php receive_logs_topic.php "kern.*" "*.critical"
```

发送kern.critical日志：

```
php emit_log_topic.php "kern.critical" "A critical kernel error"
```
