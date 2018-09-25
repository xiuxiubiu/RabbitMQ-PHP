<?php

require_once dirname(__DIR__).'/vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

$connection = new AMQPStreamConnection('127.0.0.1', 5672, 'guest', 'guest');

# channel提供了大多数的API操作方法。
$channel = $connection->channel();

# 声明队列操作是幂等性的
# 只会在队列不存在时创建
# 多次执行与一次执行的影响相同
$channel->queue_declare('hello', false, false, false);

$message = new AMQPMessage('Hello World!');
$channel->basic_publish($message, '', 'hello');

echo " [x] Sent 'Hello World!'\n";

$channel->close();
$connection->close();
