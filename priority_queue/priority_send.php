<?php

error_reporting(E_ALL);

require_once dirname(dirname(__FILE__)).'/vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;

$connection = new AMQPStreamConnection('127.0.0.1', 5672, 'guest', 'guest');
$channel = $connection->channel();

$args = new AMQPTable(['x-max-priority'=>255]);
$channel->queue_declare('priority_queue', false, false, false, false, false, $args);

$data = implode(' ', array_slice($argv, 1));
if (empty($data)) {
    $data = 'Hello World!';
}

$properties = isset($argv[1]) && !empty($argv[1]) ? ['priority'=>(int)$argv[1]] : [];
$message = new AMQPMessage($data, $properties);
$channel->basic_publish($message, '', 'priority_queue');

echo ' [x] Sent ', $data, "\n";

$channel->close();
$connection->close();
