<?php
/**
 * Project ArrowWorker
 * User:   louis
 * Date:   18-1-1
 */

namespace ArrowWorker\Driver\Session;
use ArrowWorker\Driver\Session;

/**
 * Class RedisSession
 * @package ArrowWorker\Driver\Session
 */
class RedisSession extends Session
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
     * connect to session server
     * @return bool
     * @throws \Exception
     */
    public function connect()
	{
        if( !extension_loaded("redis") )
        {
            throw new \Exception('please install redis extension',500);
        }
        $this->handler = new \Redis();
        if( !$this->handler->connect($this->host, $this->port) )
        {
            throw new \Exception('can not connect session redis',500);
            return false;
        }
        else
        {
            if( !$this->handler->auth($this->auth) )
            {
                throw new \Exception('session redis password is not correct',500);
                return false;
            }
        }
        return true;
	}

    /**
     * set specified
     * @param string $sessionId
     * @param string $key
     * @param string $val
     * @return bool
     */
    public function Set(string $sessionId, string $key, string $val) : bool
    {
        $isOk = $this->handler -> Hset($sessionId, $key, $val);
        if( $isOk === false )
        {
            return false;
        }
        return true;
    }

    /**
     * MSet : set session information by array
     * @param string $sessionId
     * @param array $val
     * @return bool
     */
    public function MSet(string $sessionId, array $val) : bool
    {
        return $this->handler -> hMset($sessionId, $val);
    }

    /**
     * get specified information in specified session
     * @param string $sessionId
     * @param string $key
     * @return mixed
     */
    public function Get(string $sessionId, string $key)
    {
        return $this->handler -> Hget($sessionId, $key);
    }

    /**
     * delete specified key in specified session
     * @param string $sessionId
     * @param string $key
     * @return int
     */
    public function Del(string $sessionId, string $key) : bool 
    {
        if( $this->handler->hDel($sessionId, $key)>0 )
        {
            return true;
        }
        return true ;
    }

    /**
     * destory specified session
     * @param string $sessionId
     * @return mixed
     */
    public function Destory(string $sessionId) : bool
    {
        if( $this->handler->del($sessionId)>0 )
        {
            return true;
        }
        return true;
    }


    /**
     * verify if the specified session exists
     * @param string $sessionId
     * @return bool
     */
    public function Exists(string $sessionId) : bool
    {
        return $this->handler -> exits( $sessionId );
    }


    /**
     * verify if the specified session key exists
     * @param string $sessionId
     * @return mixed
     */
    public function KeyExits(string $sessionId, string $key) : bool
    {
        return $this->handler -> hExists( $sessionId, $key);
    }

    /**
     * get all session information
     * @param string $sessionId
     * @return mixed
     */
    public function Info(string $sessionId) : array
    {
        return  $this->handler -> hGetAll( $sessionId );
    }

}

