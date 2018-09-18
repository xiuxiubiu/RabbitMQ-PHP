<?php

require_once dirname(dirname(__FILE__)).'/vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

$connection = new AMQPStreamConnection('127.0.0.1', 5672, 'guest', 'guest');
$channel = $connection->channel();
$channel->queue_declare('hello', false, false, false);

$message = new AMQPMessage('Hello World!');

$channel->basic_publish($message, '', 'hello');

echo " [x] Sent 'Hello World!'\n";

$channel->close();
$connection->close();