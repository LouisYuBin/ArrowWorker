<?php

namespace ArrowWorker\Client\Tcp;

use ArrowWorker\Log\Log;
use swoole\client as SwClient;


class Client
{
    /**
     * @var Client
     */
    private $_client;

    /**
     * @var string
     */
    private $_host;

    /**
     * @var int
     */
    private $_port;

    /**
     * @var float|int
     */
    private $_timeout = 3;

    /**
     * @var string
     */
    private $_logName = 'Tcp_Client';

    /*
     * 13 connect refused
     * 32 pipe broken
     * */


    /**
     * @param string $host
     * @param int $port
     * @param float $timeout
     * @param int $connRetryTimes
     * @return
     */
    public static function Init(string $host, int $port, float $timeout = 3, int $connRetryTimes = 3)
    {
        return new self($host, $port, $timeout, $connRetryTimes);
    }

    /**
     * Tcp constructor.
     *
     * @param string $host
     * @param int $port
     * @param float $timeout
     * @param int $connTryTimes
     */
    public function __construct(string $host, int $port, float $timeout = 3, int $connTryTimes = 3)
    {
        $this->_host = $host;
        $this->_port = $port;
        $this->_timeout = $timeout;

        $this->InitClient($connTryTimes);
    }

    /**
     * @param int $connTryTimes
     *
     * @return bool
     */
    public function InitClient(int $connTryTimes = 3)
    {
        $this->_client = new SwClient(SWOOLE_SOCK_TCP);

        for ($i = 0; $i < $connTryTimes; $i++) {
            try {
                if (@$this->_client->connect($this->_host, $this->_port, $this->_timeout)) {
                    return true;
                }

            } catch (\Exception $e) {
                Log::Error("connect failed : {host}:{}, error code : {code}", [
                    'host' => $this->_host,
                    'port' => $this->_port,
                    'code' => $this->_client->errCode
                ], $this->_logName);
            }
        }

        return false;
    }

    /**
     * @return bool
     */
    public function IsConnected(): bool
    {
        if ($this->_client->errCode > 0) {
            return false;
        }

        return true;
    }

    /**
     * @param string $data
     * @param int $retryTimes
     *
     * @return bool
     */
    public function Send(string $data, int $retryTimes = 3)
    {
        if (!$this->IsConnected()) {
            if (!$this->InitClient($retryTimes)) {
                return false;
            }
        }

        for ($i = 0; $i < $retryTimes; $i++) {
            try {
                $result = @$this->_client->send($data);
            } catch (\Exception $e) {
                Log::Error("send data failed : {host}:{port}, error code : {code} , data : {$data}", [
                    'host' => $this->_host,
                    'port' => $this->_port,
                    'code' => $this->_client->errCode
                ], $this->_logName);
                $this->InitClient($retryTimes);
                $result = false;
            }

            if (true == $result) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return mixed
     */
    public function Receive()
    {
        if (!$this->IsConnected()) {
            false;
        }

        return $this->_client->recv();
    }

    /**
     * @return mixed
     */
    public function Close()
    {
        return $this->_client->close();
    }

    /**
     *
     */
    public function __destruct()
    {
        // todo
    }
}