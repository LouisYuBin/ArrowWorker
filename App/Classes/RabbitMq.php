<?php

/*
 * Author : louis
 * Date   : 2017-02-08 15:26:00
 */

namespace App\Classes;

class RabbitMq
{
    //队列和交换机flag（属性） 
    //const QUEUE_FLAG    = AMQP_DURABLE | AMQP_AUTODELETE;
    const QUEUE_FLAG = AMQP_DURABLE;
    //交换机类型
    const EXCHANGE_TYPE = AMQP_EX_TYPE_DIRECT;
    //默认交换机名称
    const EXCHANGE_NAME = 'defaultExchange';
    //默认交换机通道
    const EXCHANGE_ROUTE = 'defaultRoute';
    //默认队列名称
    const QUEUE_NAME = 'defaultQueue';
    //队列应答模式
    const CALLBACK = AMQP_AUTOACK;

    private $conn;      //连接对象
    private $channel;   //虚拟主机
    private $exchange;  //交换机
    private $queue;     //队列
    private $config;    //连接信息
    private $routeName; //路由名称
    private $exName;    //交换机名称
    private $isQueueInited = false;

    public function __construct($config = null)
    {
        if (is_null($config)) {
            $this->config = [
                'host'     => '127.0.0.1',
                'port'     => '5672',
                'login'    => 'admin',
                'password' => 'admin',
                'vhost'    => '/',
            ];
        }
        unset($config['exchange'], $config['route'], $config['queue']);
        $this->config = $config;
        $this->_connect();
    }

    private function _connect()
    {
        $this->conn = new \AMQPConnection($this->config);
        if (!$this->conn->connect()) {
            echo "Cannot connect to the broker." . PHP_EOL;
        }

        $this->channel = new \AMQPChannel($this->conn);
        $this->exchange = new \AMQPExchange($this->channel);
        $this->queue = new \AMQPQueue($this->channel);
    }

    //创建交换机
    public function initExchange($exName, $type = self::EXCHANGE_TYPE, $property = self::QUEUE_FLAG)
    {
        $this->exName = $exName;
        $this->exchange->setName($this->exName);
        //$this -> exchange -> setType( $type );
        //$this -> exchange -> setFlags( $property );	
    }

    //初始化队列
    public function initQueue($queueName, $routName, $property = self::QUEUE_FLAG)
    {
        $this->routeName = $routName;
        $this->queue->setName($queueName);
        //$this -> queue -> setFlags( $property );
    }

    //初始化交换机和队列
    public function init($exchange = self::EXCHANGE_NAME, $routName = self::EXCHANGE_ROUTE, $queueName = self::QUEUE_NAME, $reInit = false)
    {
        if (!$this->isQueueInited || $reInit) {
            $this->initExchange($exchange);
            $this->initQueue($queueName, $routName);
            $isQueueInited = true;
        }
    }

    //入队列
    public function push($message)
    {
        return $this->exchange->publish($message, $this->routeName);
    }

    //出队列:1则自动应答  0则手动应答
    public function pop($property = 1)
    {
        $property = ($property == 1) ? self::CALLBACK : null;
        $message = false;
        if (is_null($property)) {
            $message = $this->queue->get();
        } else {
            $message = $this->queue->get($property);
        }
        if ($message) {
            return [
                'msg' => $message->getBody(),
                'ack' => $message->getDeliveryTag(),
            ];
        } else {
            return false;
        }
    }

    public function ack($deliveryTag)
    {
        $this->queue->ack($deliveryTag);
    }

    //出队列(阻塞模式)
    public function consume($function, $property = 1)
    {
        $property = ($property == 1) ? self::CALLBACK : null;
        if (is_null($property)) {
            $this->queue->consume($function);
        } else {
            $this->queue->consume($function, $property);
        }
    }

    //关闭连接
    public function close()
    {
        $this->__destruct();
    }

    public function __destruct()
    {
        $this->conn->disconnect();
    }

} 
