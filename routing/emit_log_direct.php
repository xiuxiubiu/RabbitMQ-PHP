<?php

require_once dirname(__DIR__).'/vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

$connection = new AMQPStreamConnection('127.0.0.1', 5672, 'guest', 'guest');
$channel = $connection->channel();

$channel->exchange_declare('direct_logs', 'direct', false, false, false);

$serverity = isset($argv[1]) && !empty($argv[1]) ? $argv[1] : 'info';

$data = implode(' ', array_slice($argv, 1));
if (empty($data)) {
    $data = 'Hello World!';
}

$messaga = new AMQPMessage($data);

$channel->basic_publish($messaga, 'direct_logs', $serverity);

echo ' [x] Sent ', $severity, ':', $data, "\n";

$channel->close();
$connection->close();
