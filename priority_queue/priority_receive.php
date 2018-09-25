<?php

require_once dirname(__DIR__).'/vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Wire\AMQPTable;

$connection = new AMQPStreamConnection('127.0.0.1', 5672, 'guest', 'guest');
$channel = $connection->channel();

// 设置队列最大优先级
$args = new AMQPTable(['x-max-priority'=>255]);
$channel->queue_declare('priority_queue', false, false, false, false, false, $args);

echo " [*] Waiting for data. To exit press CTRL+C\n";

$callback = function ($message) {
    echo ' [x] Received ', $message->body, "\n";
    sleep(10);
    $message->delivery_info['channel']->basic_ack($message->delivery_info['delivery_tag']);
    echo " [x] Done\n";
};

// 公平分发必须开启ack
$channel->basic_qos(0, 1, false);
$channel->basic_consume('priority_queue', '', false, false, false, false, $callback);

while (count($channel->callbacks)) {
    $channel->wait();
}

$channel->close();
$connection->close();
