<?php

require_once dirname(dirname(__FILE__)).'/vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

$connection = new AMQPStreamConnection('127.0.0.1', 5672, 'guest', 'guest');
$channel = $connection->channel();

$channel->exchange_declare('topic_logs', 'topic', false, false, false);

$routing_key = isset($argv[1]) && !empty($argv[1]) ? $argv[1] : 'anonymous.info';

$data = implode(' ', array_slice($argv, 2));
if (empty($data)) {
    $data = 'Hello World!';
}

$message = new AMQPMessage($data);

$channel->basic_publish($message, 'topic_logs', $routing_key);

echo ' [x] Sent ', $routing_key, ':', $data, "\n";

$channel->close();
$connection->close();
