<?php
/**
 * Project ArrowWorker
 * User:   louis
 * Date:   18-1-1
 */

namespace ArrowWorker\Driver\Session;
use ArrowWorker\Driver\Session;
use ArrowWorker\Log;

/**
 * Class MemcachedSession
 * @package ArrowWorker\Driver\Session
 */
class MemcachedSession extends Session
{


    /**
     * Session constructor.
     * @param string $host
     * @param int $port
     * @param string $userName
     * @param string $password
     * @param int $timeout
     */
    public function __construct(string $host, int $port, string $userName, string $password, int $timeout)
    {
        parent::__construct($host, $port, $userName, $password, $timeout);
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
            Log::DumpExit("memcached extension is not installed.");
        }

        $this->handler = new \Memcached();

        if ($this->timeout > 0)
        {
            $this->handler->setOption( \Memcached::OPT_CONNECT_TIMEOUT, $this->timeout );
        }

        if( !$this->handler->addServer($this->host, $this->port) )
        {
            Log::DumpExit("memcached add server failed.".$this->handler->getLastError());
        }

        $this->handler->setOption(\ Memcached::SERIALIZER_JSON, true);

        if ('' != $this->userName)
        {
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

        $info[$key] = $val;
        return $this->handler -> replace($sessionId, $info);

    }

    /**
     * MSet : set session information by array
     * @param string $sessionId
     * @param array $val
     * @return bool
     */
    public function MSet(string $sessionId, array $val) : bool
    {
        $info = $this->handler->get($sessionId);
        if( false===$info )
        {
            return $this->handler -> set($sessionId, $val);
        }

        return $this->handler -> set($sessionId, array_merge($info,$val));
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


    /**
     * get all session information
     * @param string $sessionId
     * @return mixed
     */
    public function Info(string $sessionId) : array
    {
        $session =  $this->handler -> get( $sessionId );
        if( is_array($session) )
        {
            return $session;
        }
        return [];
    }

}

