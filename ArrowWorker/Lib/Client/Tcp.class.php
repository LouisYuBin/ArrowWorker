<?php
/**
 * By yubin at 2019/4/17 6:28 AM.
 */

namespace ArrowWorker\Lib\Client;

use \swoole\client as Client;

/**
 * Class Tcp
 * @package ArrowWorker\Lib\Client
 */
class Tcp
{
    /**
     * @var Client
     */
    private $_client = null;

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
     * @param string $host
     * @param int    $port
     * @param float  $timeout
     * @return Tcp
     */
    public static function Init(string $host, int $port, float $timeout=3)
    {
        return new self($host, $port, $timeout);
    }

    /**
     * Tcp constructor.
     * @param string $host
     * @param int    $port
     * @param float  $timeout
     */
    private function __construct(string $host, int $port, float $timeout)
    {
        $this->_host    = $host;
        $this->_port    = $port;
        $this->_timeout = $timeout;

        $this->_initClient();
    }

    /**
     * @return bool
     */
    private function _initClient()
    {
        $client = new Client(SWOOLE_SOCK_TCP);
        if ( @!$client->connect($this->_host, $this->_port, 3) )
        {
            return false;
        }
        $this->_client = $client;
        return true;
    }

    /**
     * @param string $data
     * @return mixed
     */
    public function Send(string $data, int $retryTimes=3)
    {
        if( is_null($this->_client) )
        {
            false;
        }
        for( $i=0; $i<3; $i++ )
        {
            if( true==$this->_client->send($data) )
            {
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
        if( is_null($this->_client) )
        {
            false;
        }
        return $this->_client->recv();
    }

    /**
     * @return mixed
     */
    public function Close()
    {
        if( is_null($this->_client) )
        {
            false;
        }
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