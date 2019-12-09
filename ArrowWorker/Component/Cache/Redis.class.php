<?php

namespace ArrowWorker\Component\Cache;

use ArrowWorker\Log;

class Redis implements Cache
{

    const LOG_PREFIX = "[  Redis  ] ";

    /**
     * @var \Redis
     */
    private $_conn;

    /**
     * @var array
     */
    private $_config = [];

    /**
     * @param array $config
     */
    public function __construct( array $config )
    {
        $this->_config = $config;
    }

    /**
     * @return bool
     */
    public function InitConnection() : bool
    {
        @$this->_conn = new \Redis();

        try
        {
            if ( false === @$this->_conn->connect( $this->_config['host'], $this->_config['port'] ) )
            {
                Log::Dump( self::LOG_PREFIX."connect failed, error message : ".$this->_conn->getLastError()." config : ".json_encode($this->_config) );
                return false;
            }
        }
        catch (\RedisException $e)
        {
            Log::Dump( self::LOG_PREFIX."connect failed, error message : ".$e->getMessage()." config : ".json_encode($this->_config) );
            return false;
        }

        if( ''==$this->_config['password'] )
        {
            return true;
        }

        if( !$this->_conn->auth( $this->_config['password'] ) )
        {
            return false;
        }

        return true;
    }

    /**
     * @param array $config
     * @return Redis
     */
    public static function Init( array $config)
    {
        return new self($config);
    }

	/**
	 * Db 选择数据库
	 * @param int $dbName
	 * @return bool
	 */
	public function Db(int $dbName) : bool
    {
         return $this->_exec('select','');
    }


	/**
	 * Set : write cache
	 * @param $key
	 * @param $val
	 * @return bool
	 */
	public function Set(string $key, string $val) : bool
    {
        return $this->_exec('set', $key, $val );
    }

    /**
     * @param $key
     * @param $val
     * @return bool
     */
    public function SetNx(string $key, string $val) : bool
    {
        return $this->_exec('setnx', $key, $val );
    }

    /**
     * @param string $key
     * @param string $val
     * @param int $ttl
     * @return bool
     */
    public function SetEx(string $key, string $val, int $ttl) : bool
    {
        return $this->_exec('setex', $key, $val, $ttl );
    }


	/**
	 * Get : read cache
	 * @param string $key
	 * @return string|false
	 */
	public function Get(string $key)
    {
        return $this->_exec('get', $key);
    }

    /**
     * @param string $key
     * @return string|false
     */
    public function Del(string $key) : bool
    {
        return $this->_exec('del', $key);
    }

    public function Exists( string $key) : bool
    {
        return $this->_exec('exists', $key);
    }

    /**
     * @param string $key
     * @param string $value
     * @return mixed
     */
    public function Append( string $key, string $value)
    {
        return $this->_exec('append', $key, $value);
    }

    /**
     * @param int $option
     * @return mixed
     */
    public function Multi( int $option=\Redis::MULTI)
    {
        return $this->_exec('multi', $option);
    }

    /**
     * Watches a key for modifications by another client. If the key is modified between WATCH and EXEC,
     * the MULTI/EXEC transaction will fail (return FALSE). unwatch cancels all the watching of all keys by this client.
     * @param string | array $key: a list of keys
     * @return void
     * @link    https://redis.io/commands/watch
     * @example
     * <pre>
     * $redis->watch('x');
     * // long code here during the execution of which other clients could well modify `x`
     * $ret = $redis->multi()
     *          ->incr('x')
     *          ->exec();
     * // $ret = FALSE if x has been modified between the call to WATCH and the call to EXEC.
     * </pre>
     */
    public function Watch(string $key)
    {
        return $this->_exec('watch', $key);
    }


	/**
	 * Lpush : Adds the string values to the head (left) of the list. Creates the list if the key didn't exist.
     * If the key exists and is not a list, FALSE is returned
	 * @param string $queue
	 * @param string $val
	 * @return int|false
	 */
	public function LPush(string $queue, string $val)
    {
        return $this->_exec('lPush',$queue, $val);
    }


	/**
	 * Rpush : Adds the string values to the tail (right) of the list. Creates the list if the key didn't exist.
     * If the key exists and is not a list, FALSE is returned.
	 * @param string $queue
	 * @param string $val
	 * @return int|false
	 */
	public function RPush(string $queue, string $val)
    {
        return $this->_exec('rPush', $queue, $val);
    }


	/**
	 * Rpop : Returns and removes the last element of the list.
	 * @param  string $queue
	 * @return string|false
	 * Return value：STRING if command executed successfully BOOL FALSE in case of failure (empty list)
	 */
	public function RPop(string $queue)
    {
        return $this->_exec('rPop', $queue);
    }

	/**
	 * Lpop : Returns and removes the first element of the list.
	 * @param string $queue
	 * @return string|false
	 * Return value：STRING if command executed successfully BOOL FALSE in case of failure (empty list)
	 */
	public function LPop(string $queue)
    {
        return $this->_exec('lPop', $queue);
    }


	/**
	 * BrPop : Is a blocking rPop primitive. If at least one of the lists contains at least one element,
     * the element will be popped from the head of the list and returned to the caller.
     * Il all the list identified by the keys passed in arguments are empty, brPop will
     * block during the specified timeout until an element is pushed to one of those lists. T
     * his element will be popped.
	 * @param string $queue
	 * @param int $timeout
	 * @return array
	 *  Return value ：ARRAY array('listName', 'element')
	 */
	public function BrPop( string $queue, int $timeout )
    {
        return $this->_exec('brPop', $queue, $timeout);
    }

	/**
	 * BlPop : Is a blocking lPop primitive. If at least one of the lists contains at least one element,
     * the element will be popped from the head of the list and returned to the caller.
     * Il all the list identified by the keys passed in arguments are empty, blPop will block
     * during the specified timeout until an element is pushed to one of those lists. This element will be popped.
	 * @param string|array $queue
	 *    Parameters：ARRAY Array containing the keys of the lists INTEGER Timeout Or STRING Key1 STRING Key2 STRING Key3 ... STRING Keyn INTEGER Timeout
	 * @param int $timeout
	 * @return mixed
	 * 	  ARRAY array('listName', 'element')
	 */
	public function BlPop(string $queue, int $timeout)
    {
        return $this->_exec('blPop', $queue, $timeout);

    }

	/**
	 * Hset hash table 写入
	 * @param string $key
	 * @param string $hashKey
	 * @param string $value
	 * @return mixed
	 *       LONG 1 if value didn't exist and was added successfully, 0 if the value was already present and was replaced, FALSE if there was an error.
	 */
	public function HSet(string $key, string $hashKey, string $value)
    {
        return $this->_exec('hSet', $key, $hashKey, $value);
    }

    /**
     * @param string $key
     * @param string $hashKey
     * @param string $value
     * @return mixed
     */
    public function HSetNx( string $key, string $hashKey, string $value)
    {
        return $this->_exec('hSetNx', $hashKey, $value);
    }

    /**
     * Hset hash table 写入
     * @param string $key
     * @param array $values
     * @return mixed
     *       LONG 1 if value didn't exist and was added successfully, 0 if the value was already present and was replaced, FALSE if there was an error.
     */
    public function HmSet(string $key, array $values)
    {
        return $this->_execMulti('hMSet', $key, $values);
    }

    /**
     * Hset hash table 写入
     * @param string $key
     * @param string ...$hashKeys
     * @param string $value
     * @return mixed
     *       LONG 1 if value didn't exist and was added successfully, 0 if the value was already present and was replaced, FALSE if there was an error.
     */
    public function HmGet(string $key, string ...$hashKeys)
    {
        return $this->_exec('hMGet', $key, ...$hashKeys);
    }

	/**
	 * Hget hashTable 读取
	 * @param $key
	 * @param $hashKey
	 * @return mixed
	 * 		STRING The value, if the command executed successfully BOOL FALSE in case of failure
	 */
	public function HGet(string $key, string $hashKey)
    {
        return $this->_exec('hGet', $key, $hashKey);
    }


	/**
	 * Hlen hashTable 长度
	 * @param string $key
	 * @return mixed
	 *     LONG the number of items in a hash, FALSE if the key doesn't exist or isn't a hash.
	 */
	public function HLen(string $key)
    {
        return $this->_exec('hLen', $key);
    }

    /**
     * @param string $key
     * @return bool
     */
    public function HExists( string $key, string $hashKey) : bool
    {
        return $this->_exec('hExists', $key, $hashKey);
    }

    /**
     * @param string $key
     * @param string $hashKey
     * @return bool
     */
    public function HDel( string $key, string $hashKey) : bool
    {
        return $this->_exec('hDel', $key, $hashKey);
    }

    /**
     * @param string $key
     * @return mixed
     */
    public function HIncrBy( string $key, int $step)
    {
        return $this->_exec('hIncrBy', $key, $step);
    }

    /**
     * @param string $key
     * @return bool|array
     */
    public function HKeys( string $key)
    {
        return $this->_exec('hKeys', $key);
    }

    /**
     * @param string $key
     * @return bool|array
     */
    public function HVals( string $key)
    {
        return $this->_exec('hVals', $key);
    }

    /**
     * @param string $key
     * @return bool|array
     */
    public function HGetAll( string $key)
    {
        return $this->_exec('hGetAll', $key);
    }


    /**
     * @param string $key
     * @return bool|int
     */
    public function Decr( string $key)
    {
        return $this->_exec('decr', $key);
    }

    /**
     * @param string $key
     * @param int    $step
     * @return bool|int
     */
    public function DecrBy( string $key, int $step)
    {
        return $this->_exec('decrBy', $key, $step);
    }


    /**
     * @param string $key
     * @return bool|int
     */
    public function Incr( string $key)
    {
        return $this->_exec('incr', $key);
    }

    /**
     * @param string $key
     * @param int    $step
     * @return bool|int
     */
    public function IncrBy( string $key, int $step)
    {
        return $this->_exec('incrBy', $key, $step);
    }


    /**
     * @param string $channel
     * @param string $msg
     * @return mixed
     */
    public function Publish( string $channel, string $msg)
    {
        return $this->_exec('publish', $channel, $msg);
    }

	/**
	 * Ping
	 * @return mixed
	 * 		STRING: +PONG on success. Throws a RedisException object on connectivity error, as described above.
	 */
	public function Ping()
    {
        return $this->_exec('ping', '');
    }

    /**
     * @return mixed
     */
    public function DbSize()
    {
        return $this->_exec('dbSize', '');

    }

    /**
     * @return mixed
     */
    public function FlushDB()
    {
        return $this->_exec('flushDB', '');
    }

    /**
     * @return mixed
     */
    public function FlushAll()
    {
        return $this->_exec('flushAll', '');
    }


	/**
	 * close 关闭连接
	 * @return void
	 */
	public function Close()
    {
        $this->_conn->close();
    }


    /**
     * @param string $function
     * @param string $key
     * @param string ...$values
     * @return mixed
     */
    private function _exec( string $function, string $key, string ...$values)
    {
        $isRetried = false;
        START:
        try
        {
            switch ($function)
            {
                case 'dbSize':
                case 'flushDB':
                case 'flushAll':
                case 'ping':     //done
                    $result = $this->_conn->$function();
                    break;
                case 'get':     //done
                case 'select':   //done
                case 'del':   //done
                case 'watch':    //done
                case 'decr':     //done
                case 'incr':     //done
                case 'hKeys':    //done
                case 'hGetAll':  //done
                case 'hVals':    //done
                case 'hLen':     //done
                case 'exists':
                    $result = $this->_conn->$function($key);
                    break;
                case 'set':  //done
                    $result = $this->_conn->$function($key, $values[0], 1);
                    break;
                case 'lPush':  //done
                case 'rPush':  //done
                    $result = $this->_conn->$function( $key, $values[0] );
                    break;
                case 'rPop':   //done
                case 'lPop':   //done
                case 'brPop':  //done
                case 'blPop':  //done
                    $result = $this->_conn->$function( $key, (int)$values[0] );
                    break;
                case 'hSet':    //done
                case 'hSetNx':  //done
                    $result = $this->_conn->$function( $key, $values[0], $values[1]);
                    break;
                case 'hGet':     //done
                case 'publish':  //done
                case 'hExists':  //done
                case 'rPushx':
                case 'append':   //done
                case 'setnx':    //done
                    $result = $this->_conn->$function( $key, $values[0]);
                    break;
                case 'decrBy':  //done
                case 'incrBy':  //done
                    $result = $this->_conn->$function( $key, (int)$values[0]);
                    break;
                case 'hDel':   //done
                case 'hMGet':  //done
                    $result = $this->_conn->$function($key, $values);
                    break;
                case 'hIncrBy':  //done
                    $result = $this->_conn->$function($key, $values[0], (int)$values[1]);
                    break;
                case 'setex':  //done
                    $result = $this->_conn->$function($key, (int)$values[1], $values[0]);
                    break;
                case 'multi':    //done
                    $result = $this->_conn->$function( (int)$key );
                    break;
                default:
                    return false;
            }
        }
        catch(\RedisException $exception)
        {
            if( !$isRetried )
            {
                $this->_handleException($exception, $function);
                $isRetried = true;
                goto START;
            }
            return false;
        }
        return $result;
    }

    public function _execMulti(string $function, string $key, array $values)
    {
        $isRetried = false;
        START:
        try
        {
            switch ($function)
            {
                case 'hMSet': //单独处理
                    $result = $this->_conn->$function($key, $values);
                    break;
                default:
                    return false;
            }
        }
        catch(\RedisException $exception)
        {
            if( !$isRetried )
            {
                $this->_handleException($exception, $function);
                $isRetried = true;
                goto START;
            }
            return false;
        }
        return $result;
    }

    /**
     * @param \RedisException $exception
     * @param string          $function
     */
    private function _handleException( \RedisException $exception, string $function)
    {
        Log::Warning(__CLASS__.'::'.$function." failed, ".$exception->getMessage(), self::LOG_NAME);
        if(
            false!==strpos($exception->getMessage(), 'server went away') ||
            false!==strpos($exception->getMessage(), 'Connection lost')
        )
        {
            Log::Warning('Trying to reconnect.', self::LOG_NAME);
            $this->InitConnection();
        }
    }

}