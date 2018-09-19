<?php

require_once dirname(dirname(__FILE__)).'/vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;

$connection = new AMQPStreamConnection('127.0.0.1', 5672, 'guest', 'guest');
$channel = $connection->channel();

$args = new AMQPTable(['x-delayed-type' => 'direct']);
$channel->exchange_declare('my-delayed', 'x-delayed-message', false, true, false, false, false, $args);

$data = implode(' ', array_slice($argv, 1));
if (empty($data)) {
    $data = 'Hello World!';
}

$properties = [
    'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
    'application_headers' => new AMQPTable(['x-delay'=>5000]),
];
$message = new AMQPMessage($data, $properties);

$channel->basic_publish($message, 'my-delayed', '', false, false);

echo ' [x] Sent ', $data, "\n";

$channel->close();
$connection->close();

