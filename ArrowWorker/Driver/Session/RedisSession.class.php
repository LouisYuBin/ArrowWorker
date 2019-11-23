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
        $this->Connect();
    }

    /**
     * connect to session server
     * @return bool
     */
    public function Connect()
	{
        if( !extension_loaded("redis") )
        {
            Log::DumpExit("redis extension is not installed.");
        }
        $this->handler = new \Redis();
        if( !$this->handler->connect($this->host, $this->port) )
        {
            Log::DumpExit("can not connect session redis failed, reason : ".$this->handler->getLastError());
        }
        else
        {
            if( !$this->handler->auth($this->auth) )
            {
                Log::DumpExit("check session redis authorisation failed, reason : ".$this->handler->getLastError());
            }
        }
        return true;
	}

    /**
     * set specified
     * @param string $token
     * @param string $key
     * @param string $val
     * @return bool
     */
    public function Set(string $token, string $key, string $val) : bool
    {
        $isOk = $this->handler->Hset($token, $key, $val);
        if( $isOk === false )
        {
            return false;
        }
        return true;
    }

    /**
     * @param string $token
     * @param array $val
     * @return bool
     */
    public function MSet(string $token, array $val) : bool
    {
        return $this->handler->hMset($token, $val);
    }

    /**
     * @param string $token
     * @param string $key
     * @return mixed
     */
    public function Get(string $token, string $key)
    {
        return $this->handler->Hget($token, $key);
    }

    /**
     * @param string $token
     * @param string $key
     * @return int
     */
    public function Del(string $token, string $key) : bool
    {
        if( $this->handler->hDel($token, $key)>0 )
        {
            return true;
        }
        return true ;
    }

    /**
     * Destroy specified session
     * @param string $token
     * @return mixed
     */
    public function Destroy(string $token) : bool
    {
        if( $this->handler->del($token)>0 )
        {
            return true;
        }
        return true;
    }


    /**
     * @param string $token
     * @return bool
     */
    public function Exists(string $token) : bool
    {
        return $this->handler->exists( $token );
    }


    /**
     * verify if the specified session key exists
     * @param string $token
     * @param string $key
     * @return mixed
     */
    public function KeyExits(string $token, string $key) : bool
    {
        return $this->handler->hExists( $token, $key);
    }

    /**
     * @param string $token
     * @return mixed
     */
    public function Info(string $token) : array
    {
        return  $this->handler->hGetAll( $token );
    }

}

