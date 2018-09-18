<?php

require_once dirname(dirname(__FILE__)).'/vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

$connection = new AMQPStreamConnection('127.0.0.1', 5672, 'guest', 'guest');
$channel = $connection->channel();
$channel->queue_declare('hello', false, true, false);

$data = implode(' ', array_slice($argv, 1));
if (empty($data)) {
    $data = 'Hello World!';
}

$properties = [
    'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
];
$message = new AMQPMessage($data, $properties);

$channel->basic_publish($message, '', 'hello');

echo ' [x] Sent ', $data, "\n";

$channel->close();
$connection->close();

