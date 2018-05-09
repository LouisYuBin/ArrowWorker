<?php
/**
 * Project ArrowWorker
 * User:   louis
 * Date:   18-1-1
 */

namespace ArrowWorker\Driver\Session;

/**
 * Class MemcachedSession
 * @package ArrowWorker\Driver\Session
 */
class MemcachedSession
{
    /**
     * @var
     */
    private $handler;
    /**
     * @var string
     */
    private $host = '127.0.0.1';

    /**
     * @var int
     */
    private $port = 6379;

    /**
     * @var string
     */
    private $userName = '';

    /**
     * @var string
     */
    private $auth = '';
    
    /**
     * @var int
     */
    private $timeout = 0;


    /**
     * MemcachedSession constructor.
     * @param string $host
     * @param int $port
     * @param string $userName
     * @param string $password
     * @param int $timeout
     */
    public function __construct(string $host, int $port, string $userName, string $password, int $timeout)
	{
		$this->host = $host;
		$this->port = $port;
		$this->auth = $password;
		$this->timeout  = $timeout;
        $this->userName = $userName;
		$this->connect();
	}

    /**
     * @return bool
     * @throws \Exception
     */
    public function connect()
	{
        if( !extension_loaded("memcached") )
        {
            throw new \Exception('please install memcached extension',500);
        }

        $this->handler = new \Memcached();

        if ($this->timeout > 0)
        {
            $this->handler->setOption( \Memcached::OPT_CONNECT_TIMEOUT, $this->timeout );
        }

        if( !$this->handler->addServer($this->host, $this->port) )
        {
            throw new \Exception('can not connect session memcached',500);
            return false;
        }

        $this->handler->setOption(\ Memcached::SERIALIZER_JSON, true);

        if ('' != $this->userName) {
            $this->handler->setOption(\Memcached::OPT_BINARY_PROTOCOL, true);
            $this->handler->setSaslAuthData($this->userName, $this->auth);
        }

        return true;
	}

    /**
     * set session key information
     * @param string $sessionId
     * @param string $key
     * @param string $val
     * @return bool
     */
    public function Set(string $sessionId, string $key, string $val) : bool
    {
        $info = $this->handler->get($sessionId);
        if( false === $info)
        {
            return $this->handler -> set($sessionId, [$key=>$val]);
        }

        if( is_array($info) )
        {
            $info[$key] = $val;
            return $this->handler -> set($sessionId, $info);
        }

        return false;
    }

    /**
     * get specified information in specified session
     * @param string $sessionId
     * @param string $key
     * @return mixed
     */
    public function Get(string $sessionId, string $key)
    {
        $info = $this->handler -> get($sessionId);
        if( is_array($info) && isset($info[$key]) )
        {
            return $info[$key];
        }

        return false;
    }

    /**
     * delete specified key in specified session
     * @param string $sessionId
     * @param string $key
     * @return int
     */
    public function Del(string $sessionId, string $key) : bool
    {
        $info = $this->handler->get($sessionId);
        if( is_array($info) )
        {
            unset( $info[$key] );
            return $this->Set($sessionId, $info);
        }
        return false;
    }



    /**
     * destory specified session
     * @param string $sessionId
     * @return mixed
     */
    public function Destory(string $sessionId) : int
    {
        return $this->handler -> delete( $sessionId );
    }


    /**
     * verified if the specified session exists
     * @param string $sessionId
     * @return bool
     */
    public function Exists(string $sessionId) : bool
    {
        if( $this->handler->get($sessionId) === false )
        {
            return false;
        }
        return true;
    }


    /**
     * verified if the specified session key exists
     * @param string $sessionId
     * @return mixed
     */
    public function KeyExits(string $sessionId, string $key) : bool
    {
        if( false === $this->Get($sessionId, $key) )
        {
            return false;
        }
        return true;
    }


}

