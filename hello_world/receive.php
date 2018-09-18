<?php

require_once dirname(dirname(__FILE__)).'/vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;

$connection = new AMQPStreamConnection('127.0.0.1', 5672, 'guest', 'guest');
$channel = $connection->channel();

$channel->queue_declare('hello', false, false, false);

echo " [*] Waiting for messages. To exit press CTRL+C\n";

$callback = function ($message) {
    echo ' [x] Received ', $message->body, "\n";
};

$channel->basic_consume('hello', '', false, true, false, false, $callback);

while (count($channel->callbacks)) {
    $channel->wait();
}