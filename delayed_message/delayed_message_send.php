<?php

require_once dirname(__DIR__).'/vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;

$connection = new AMQPStreamConnection('127.0.0.1', 5672, 'guest', 'guest');
$channel = $connection->channel();

$args = new AMQPTable(['x-delayed-type' => 'direct']);
$channel->exchange_declare('my-delayed', 'x-delayed-message', false, true, false, false, false, $args);


$message5000 = new AMQPMessage('延迟5000毫秒', ['application_headers' => new AMQPTable(['x-delay'=>5000])]);
$channel->basic_publish($message5000, 'my-delayed', '', false, false);

$message1000 = new AMQPMessage('延迟1000毫秒', ['application_headers' => new AMQPTable(['x-delay'=>1000])]);
$channel->basic_publish($message1000, 'my-delayed', '', false, false);

echo ' [x] Sent Success!', "\n";

$channel->close();
$connection->close();

