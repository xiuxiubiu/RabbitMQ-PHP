<?php

require_once dirname(__DIR__).'/vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;

$connection = new AMQPStreamConnection('127.0.0.1', 5672, 'guest', 'guest');
$channel = $connection->channel();
$channel->queue_declare('hello', false, true, false);

$callback = function ($message) {
    echo ' [x] Received ', $message->body, "\n";
    sleep(substr_count($message->body, '.'));
    echo " [x] Done\n";
    $message->delivery_info['channel']->basic_ack($message->delivery_info['delivery_tag']);
};

$channel->basic_qos(null, 1, null);
$channel->basic_consume('hello', '', false, false, false, false, $callback);

while (count($channel->callbacks)) {
    $channel->wait();
}

$channel->close();
$connection->close();
