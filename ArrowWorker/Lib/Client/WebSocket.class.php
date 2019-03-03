<?php
/**
 * Created by PhpStorm.
 * User: louis
 * Date: 19-3-3
 * Time: 下午6:30
 */

namespace ArrowWorker\Lib\Client;

use \Swoole\Coroutine\Http\Client;


class WebSocket
{
    private $_instance = null;
    private $_uri      = '/';
    private $_message  = '';

    private function __construct(string $host, int $port=80)
    {
        $this->_instance = new Client($host, $port);
    }


    public static function Connect(string $host, int $port)
    {
        return new self($host, $port);
    }

    public function Push(string $data,string $uri='/') : bool
    {
        $this->_instance->upgrade( $uri );
        return  $this->_instance->push($data);
    }


}