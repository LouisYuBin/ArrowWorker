<?php
/**
 * By yubin at 2019/4/17 6:28 AM.
 */

namespace ArrowWorker\Lib\Client;


class Tcp
{
    private $_client = null;

    private $_host;

    private $_port;

    private $_timeout = 3;

    public static function Init(string $host, int $port, float $timeout=3)
    {
        return new self($host, $port, $timeout);
    }

    private function __construct(string $host, int $port, float $timeout)
    {
        $this->_host    = $host;
        $this->_port    = $port;
        $this->_timeout = $timeout;

        $this->_initClient();
    }

    private function _initClient()
    {
        $client = new \swoole\client(SWOOLE_SOCK_TCP);
        if ( @!$client->connect($this->_host, $this->_port, 3) )
        {
            return false;
        }
        $this->_client = $client;
    }

    public function Send(string $data)
    {
        if( is_null($this->_client) )
        {
            false;
        }
        return $this->_client->send($data);
    }

    public function Receive()
    {
        if( is_null($this->_client) )
        {
            false;
        }
        return $this->_client->recv();
    }

    public function Close()
    {
        if( is_null($this->_client) )
        {
            false;
        }
        return $this->_client->close();
    }

    public function __destruct()
    {
        // todo
    }
}