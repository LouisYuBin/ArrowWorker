<?php
/**
 * Created by PhpStorm.
 * User: louis
 * Date: 19-3-3
 * Time: 下午6:30
 */

namespace ArrowWorker;

use \Swoole\Http\Client;


class WebSocket
{
    private $_instance = null;

    private function __construct(string $host, int $port=80)
    {
        $this->_instance = new Client($host, $port);
    }

    public static function Connect(string $host, int $port)
    {
        return new self($host,$port);
    }

    public  function Push(string $data,int $type=WEBSOCKET_OPCODE_TEXT, $fin=1)
    {
        $this->_instance->push($data, $type, $fin);
    }

    public function Post(string $uri='',array $data)
    {
        $return = '';
        $this->_instance->post('uri', $data, function (&$return) {
            $return = $this->_instance->body;
        });
        return $return;
    }

    public function Get()
    {

    }

}