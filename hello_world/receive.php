<?php

require_once dirname(__DIR__).'/vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;

$connection = new AMQPStreamConnection('127.0.0.1', 5672, 'guest', 'guest');
$channel = $connection->channel();

# 这里声明消费队列是正确的
# 因为不能保证消费操作一定在发送操作后才执行
# 所以声明消费队列保证消费时队列存在
$channel->queue_declare('hello', false, false, false);

echo " [*] Waiting for messages. To exit press CTRL+C\n";

$callback = function ($message) {
    echo ' [x] Received ', $message->body, "\n";
};

$channel->basic_consume('hello', '', false, true, false, false, $callback);

while (count($channel->callbacks)) {
    $channel->wait();
}
