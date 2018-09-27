# 延迟队列

RabbitMQ 3.5.8以后提供了[rabbitmq_delayed_message_exchange](https://github.com/rabbitmq/rabbitmq-delayed-message-exchange)插件来实现延迟队列。

### 插件列表命令

查看RabbitMQ是否已安装rabbitmq_delayed_message_exchange：

```
rabbitmq-plugins list | grep rabbitmq_delayed_message_exchange
```

插件已安装：

```
[  ] rabbitmq_delayed_message_exchange 20171201-3.7.x
```

插件已启动：

```
[E*] rabbitmq_delayed_message_exchange 20171201-3.7.x
```

### 插件安装

首先，[Community Plugins](http://www.rabbitmq.com/community-plugins.html)找到rabbitmq_delayed_message_exchange下载插件。解压后将插件放到RabbitMQ安装目录下的plugins文件夹下。

MacOS使用brew的安装目录：

```
/usr/local/Cellar/rabbitmq/3.7.7_1/
```

CentOS使用yum的安装目录：

```
/usr/lib/rabbimtq/lib/rabbitmq_server_3.7.7/
```

实际目录根据安装版本确定。

确认插件安装成功：

```
rabbitmq-plugins list | grep rabbitmq_delayed_message_exchange
[  ] rabbitmq_delayed_message_exchange 20171201-3.7.x
```

启动插件：

```
rabbitmq-plugins enable rabbitmq_delayed_message_exchange
```

插件启动成功：
```
rabbitmq-plugins list | grep rabbitmq_delayed_message_exchange
[E*] rabbitmq_delayed_message_exchange 20171201-3.7.x
```

### 延迟消息

使用延迟队列，需要声明x-delayed-message类型的exchange：

```
$args = new AMQPTable(['x-delayed-type' => 'direct']);
$channel->exchange_declare('my-delayed', 'x-delayed-message', false, true, false, false, false, $args);
```

这里添加了一个叫做x-delayed-type的header信息，更多关于x-delayed-type的介绍查看[Routing](#routing)部分

声明exchange后，就可以在发送消息时设置消息的延迟时间了：

```
$message5000 = new AMQPMessage('延迟5000毫秒', ['application_headers' => new AMQPTable(['x-delay'=>5000])]);
$channel->basic_publish($message5000, 'my-delayed', '', false, false);

$message1000 = new AMQPMessage('延迟1000毫秒', ['application_headers' => new AMQPTable(['x-delay'=>1000])]);
$channel->basic_publish($message1000, 'my-delayed', '', false, false);
```

设置AMQMessage的properties属性中的application_headers信息里的x-delay参数指定延迟时间。如果此参数没有设置，则消息不延迟。

<div id="routing"></div>

### Routing

插件可以通过x-delayed-type参数提供灵活的路由行为。比如：设置x-delayed-type为direct，表示插件使用和direct exchange一样的路由行为，当然也可以设置为topic等等exchange类型，或者是别的插件提供的exchange。

__注意：x-delayed-type是必须的，并且指定的exchange必须要存在__

### 完整代码

[delayed_message_send.php](./delayed_message_send.php)

[delayed_message_receive.php](./delayed_message_receive.php)
