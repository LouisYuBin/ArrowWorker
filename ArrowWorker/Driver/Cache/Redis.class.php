<?php
/**
 * User: Arrow
 * Date: 2016/8/1
 * Time: 19:53
 */

namespace ArrowWorker\Driver\Cache;
use ArrowWorker\Driver\Cache as cache;


/**
 * Class Redis
 * @package ArrowWorker\Driver\Cache
 */
class Redis extends cache
{


    /**
     * init 类初始化
     * @author Louis
     * @param $config
     * @param $alias
     * @return Redis
     */
    static function Init($config, $alias) : self
    {
        if( !isset( self::$config[$alias] ))
        {
            self::$config[$alias] = $config;
        }
        self::$cacheCurrent = $alias;

        if(!self::$instance)
        {
            self::$instance = new self($config);
        }

        return self::$instance;
    }


    /**
     * getConnection 获取redis连接
     * @author Louis
     * @return \Redis
     */private function getConnection() : \Redis
    {
        if( !isset( self::$connPool[self::$cacheCurrent] ) )
        {
            $currentConfig = self::$config[self::$cacheCurrent];
            $conn = new \Redis();
            $conn -> connect($currentConfig['host'],$currentConfig['port']);

            //连接密码
            if(isset($currentConfig['password']))
            {
                $conn -> auth($currentConfig['password']);
            }

            //缓存库
            if(isset($currentConfig['db']))
            {
                $conn -> select($currentConfig['db']);
            }
            self::$connPool[self::$cacheCurrent] = $conn;
        }
        return self::$connPool[self::$cacheCurrent];
    }


	/**
	 * Db 选择数据库
	 * @param int $dbName
	 * @return mixed
	 */
	public function Db(int $dbName)
    {
        return $this -> getConnection() -> select( $dbName );
    }


	/**
	 * Set 写入key
	 * @param $key
	 * @param $val
	 * @return mixed
	 */
	public function Set(string $key, mixed $val)
    {
        return $this -> getConnection() -> set( $key, $val );
    }


	/**
	 * Get 读取key
	 * @param string $key
	 * @return mixed
	 */
	public function Get(string $key)
    {
        return $this -> getConnection() -> get($key);
    }


	/**
	 * Lpush 左入队列
	 * @param string $queue
	 * @param mixed $val
	 * @return mixed
	 */
	public function Lpush(string $queue, string $val)
    {
        return $this -> getConnection() ->lPush( $queue, $val );
    }


	/**
	 * Rpush 写队列(右)
	 * @param string $queue
	 * @param mixed $val
	 * @return mixed
	 */
	public function Rpush(string $queue, mixed $val)
    {
        return $this -> getConnection() ->rPush( $queue, $val );
    }


	/**
	 * Rpop 读队列(右)
	 * @param  string $queue
	 * @return mixed
	 * Return value：STRING if command executed successfully BOOL FALSE in case of failure (empty list)
	 */
	public function Rpop(string $queue)
    {
        return $this -> getConnection() ->rPop( $queue);
    }

	/**
	 * Lpop 左出队列
	 * @param string $queue
	 * @return mixed
	 * Return value：STRING if command executed successfully BOOL FALSE in case of failure (empty list)
	 */
	public function Lpop(string $queue)
    {
        return $this -> getConnection() ->lPop( $queue);
    }


	/**
	 * BrPop 读队列(右)(阻塞模式)
	 * @param string $queue
	 * @param int $timeout
	 * @return mixed
	 *  Return value ：ARRAY array('listName', 'element')
	 */
	public function BrPop(string $queue, int $timeout )
    {
        return $this -> getConnection() ->brPop ( $queue, $timeout );
    }

	/**
	 * BlPop 读队列(左)(阻塞模式)
	 * @param string|array $queue
	 *    Parameters：ARRAY Array containing the keys of the lists INTEGER Timeout Or STRING Key1 STRING Key2 STRING Key3 ... STRING Keyn INTEGER Timeout
	 * @param int $timeout
	 * @return mixed
	 * 	  ARRAY array('listName', 'element')
	 */
	public function BlPop(mixed $queue, int $timeout)
    {
        return $this -> getConnection() ->blPop ( $queue, $timeout );
    }

	/**
	 * Hset hash table 写入
	 * @param string $key
	 * @param string $hashKey
	 * @param mixed $value
	 * @return mixed
	 *       LONG 1 if value didn't exist and was added successfully, 0 if the value was already present and was replaced, FALSE if there was an error.
	 */
	public function Hset(sting $key, string $hashKey, mixed $value)
    {
        return $this -> getConnection() ->Hset ( $key, $hashKey, $value);
    }

	/**
	 * Hget hashTable 读取
	 * @param $key
	 * @param $hashKey
	 * @return mixed
	 * 		STRING The value, if the command executed successfully BOOL FALSE in case of failure
	 */
	public function Hget(string $key, string $hashKey)
    {
        return $this -> getConnection() ->hGet ( $key, $hashKey );
    }


	/**
	 * Hlen hashTable 长度
	 * @param string $key
	 * @return mixed
	 *     LONG the number of items in a hash, FALSE if the key doesn't exist or isn't a hash.
	 */
	public function Hlen(string $key)
    {
        return $this -> getConnection() ->hGet ( $key );
    }


	/**
	 * Ping
	 * @return mixed
	 * 		STRING: +PONG on success. Throws a RedisException object on connectivity error, as described above.
	 */
	public function Ping()
    {
        return $this -> getConnection() ->ping ();
    }


	/**
	 * close 关闭连接
	 * @return mixed
	 */
	public function close()
    {
        return $this -> getConnection() ->close();
    }

}