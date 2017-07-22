<?php
        $conn_args = array(
          'host' => '127.0.0.1',
          'port' => '5672',
          'login' => 'guest',
          'password' => 'guest',
          'vhost'=>'/'
        );

        $amqp = new AMQPConnection($conn_args);
        // Open Channel
        if (!$amqp->connect()) {
            die('Not connected ');
        }
        $channel = new AMQPChannel($amqp);
        // Declare exchange
        $exchange = new AMQPExchange($channel);
        $exchange->setName('qutest');
        $exchange->setType('fanout');
        $exchange->declare();
        // Create Queue
        $queue = new AMQPQueue($channel);
//      $queue->setName('qutest');
        $queue->declare();
        $queue->bind('qutest', 'routing.key');
        // Bind it on the exchange to routing.key
        $exchange->bind('qutest', 'routing.key');
        $data = array(
            'Name' => 'foobar',
            'Args'  => array("0", "1", "2", "3"),
        );
        //生产者，向RabbitMQ发送消息
        $message = $exchange->publish(json_encode($data), 'key');
        if (!$message) {
            echo 'Message not sent', PHP_EOL;
        } else {
            echo 'Message sent!', PHP_EOL;
        }
        //消费者
        // var_dump($queue->get(AMQP_AUTOACK));
        while ($envelope = $queue->get(AMQP_AUTOACK)) {
            echo ($envelope->isRedelivery()) ? 'Redelivery' : 'New Message';
            echo PHP_EOL;
        }
           