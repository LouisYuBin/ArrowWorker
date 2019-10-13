<?php
/**
 * Created by PhpStorm.
 * User: louis
 * Date: 19-3-3
 * Time: 下午6:30
 */

namespace ArrowWorker\Client\Ws;

use ArrowWorker\Log;
use \Swoole\Coroutine\Http\Client as SwHttpClient;


/**
 * Class WebSocket
 * @package ArrowWorker\Lib\Client
 */
class Client
{
    /**
     * @var null|Client
     */
    private $_instance = null;

    /**
     * @var string
     */
    private $_logName = 'ws_client';

    /**
     * @var string
     */
    private $_host = '127.0.0.1';

    /**
     * @var int
     */
    private $_port = 8081;

    /**
     * @var string
     */
    private $_uri = '/';

    /**
     * @var bool
     */
    private $_isSsl = false;

    /**
     * WebSocket constructor.
     * @param string $host
     * @param int    $port
     * @param string $uri
     * @param bool   $isSsl
     */
    private function __construct( string $host, int $port = 80, string $uri='/', bool $isSsl = false )
    {
        $this->_host  = $host;
        $this->_port  = $port;
        $this->_uri   = $uri;
        $this->_isSsl = $isSsl;
        $this->_instance = new SwHttpClient( $host, $port, $isSsl );
    }

    /**
     * @param int $retryTimes
     * @return bool
     */
    public function Upgrade( int $retryTimes=3)
    {
        for ( $i = 0; $i < $retryTimes; $i++ )
        {
            if ( true == $this->_instance->upgrade( $this->_uri ) )
            {
                return true;
            }
            Log::Error( "upgrade failed : {$i}th", $this->_logName );
        }
        return false;
    }

    /**
     * @param string $host
     * @param int    $port
     * @param string $uri
     * @param bool   $isSsl ;
     * @return Client
     */
    public static function Init( string $host, int $port, string $uri = '/', bool $isSsl = false )
    {
        return new self( $host, $port, $uri, $isSsl );
    }

    /**
     * @param string $data
     * @param string $uri
     * @param int    $retryTimes
     * @return bool
     */
    public function Push( string $data, int $retryTimes = 3 ) : bool
    {
        for ( $i = 0; $i < $retryTimes; $i++ )
        {
            if ( true == $this->_instance->push( $data ) )
            {
                return true;
            }
            Log::Error( "push failed : {$i}", $this->_logName );
        }
        return false;
    }

    /**
     * @param float $timeout
     * @return bool|string
     */
    public function Receive( float $timeout )
    {
        return $this->_instance->recv( $timeout );
    }

    /**
     * @return bool
     */
    public function Close()
    {
        return $this->_instance->close();
    }


}