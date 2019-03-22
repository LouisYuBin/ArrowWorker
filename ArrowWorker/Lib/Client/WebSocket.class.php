<?php
/**
 * Created by PhpStorm.
 * User: louis
 * Date: 19-3-3
 * Time: 下午6:30
 */

namespace ArrowWorker\Lib\Client;

use \Swoole\Coroutine\Http\Client;


/**
 * Class WebSocket
 * @package ArrowWorker\Lib\Client
 */
class WebSocket
{
    /**
     * @var null|Client
     */
    private $_instance = null;

    /**
     * WebSocket constructor.
     * @param string $host
     * @param int    $port
     */
    private function __construct(string $host, int $port=80)
    {
        $this->_instance = new Client($host, $port);
    }

    /**
     * @param string $host
     * @param int    $port
     * @return WebSocket
     */
    public static function Connect(string $host, int $port)
    {
        return new self($host, $port);
    }

    /**
     * @param string $data
     * @param string $uri
     * @return bool
     */
    public function Push(string $data, string $uri='/') : bool
    {
        $this->_instance->upgrade( $uri );
        return $this->_instance->push($data);
    }

    /**
     * @param float $timeout
     * @return bool|string|\Swoole\WebSocket\Frame
     */
    public function Receive(float $timeout)
    {
        return $this->_instance->recv($timeout);
    }


}