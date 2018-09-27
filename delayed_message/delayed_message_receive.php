<?php

require_once dirname(__DIR__).'/vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Wire\AMQPTable;

$connection = new AMQPStreamConnection('127.0.0.1', 5672, 'guest', 'guest');
$channel = $connection->channel();

$args = new AMQPTable(['x-delayed-type' => 'direct']);
$channel->exchange_declare('my-delayed', 'x-delayed-message', false, true, false, false, false, $args);

list($queue_name, , ) = $channel->queue_declare('', false, false, true, false);

$channel->queue_bind($queue_name, 'my-delayed', '');

echo " [*] Waiting for logs. To exit press CTRL+C\n";

$callback = function ($message) {
    echo ' [x] Received ', $message->body, "\n";
};

$channel->basic_consume($queue_name, '', false, true, false, false, $callback);

while (count($channel->callbacks)) {
    $channel->wait();
}

$channel->close();
$connection->close();