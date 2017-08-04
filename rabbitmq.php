<?php

use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Connection\AMQPConnection;

class AmqpController extends Controller
{
    private $queueRightNow = 'right.now.queue';
    private $exchangeRightNow = 'right.now.exchange';
    private $queueDelayed5sec = 'delayed.five.seconds.queue';
    private $exchangeDelayed5sec = 'delayed.five.seconds.exchange';
    private $AMQPConnection;
    private $channel;

    public function __contruct()
    {
        // create connection
        $this->AMQPConnection = new AMQPConnection('localhost',5672,'guest','guest');

        // create a channel
        $this->channel = $this->AMQPConnection->channel();
    }

    public function actionGo()
    {
        $delay = 5; // delay in seconds

        // now create the delayed queue and the exchange
        $this->channel->queue_declare(
                $this->queueDelayed5sec,
                false,
                false,
                false,
                true,
                true,
                array(
                    'x-message-ttl' => array('I', $delay*1000),   // delay in seconds to milliseconds
                   // "x-expires" => array("I", $delay*1000+10000),
                    'x-dead-letter-exchange' => array('S', $this->exchangeRightNow) // after message expiration in delay queue, move message to the right.now.queue
                )
        );
        $this->channel->exchange_declare($this->exchangeDelayed5sec, 'direct');
        $this->channel->queue_bind($this->queueDelayed5sec, $this->exchangeDelayed5sec);

        // now create a message und publish it to the delayed exchange
        $msg = new AMQPMessage(
            time(),
            array(
                'delivery_mode' => 2
            )
        );
        $this->channel->basic_publish($msg,$this->exchangeDelayed5sec);
        $this->channel->close();
        $this->AMQPConnection->close();

        return true;
    }

    public function actionConsume()
    {
        // create the right.now.queue, the exchange for that queue and bind them together
       $this->channel->queue_declare($this->queueRightNow);
       $this->channel->exchange_declare($this->exchangeRightNow, 'direct');
       $this->channel->queue_bind($this->queueRightNow, $this->exchangeRightNow);
        // consume the delayed message
        $consumeCallback = function(AMQPMessage $msg) {
            $messagePublishedAt = $msg->body;
            file_put_contents('/webdata/basic/web/time.txt', $messagePublishedAt.'-'.time());
        };
        $this->channel->basic_consume($this->queueRightNow, '', false, true, false, false, $consumeCallback);

        // start consuming
        while (count($this->channel->callbacks) > 0) {
            $this->channel->wait();
        }
    }

    public function actionDel()
    {
        $this->channel->queue_delete($this->queueRightNow );
    }
}
