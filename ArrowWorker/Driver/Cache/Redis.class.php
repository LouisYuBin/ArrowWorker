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
        self::$current = $alias;

        if(!self::$instance)
        {
            self::$instance = new self($config);
        }

        return self::$instance;
    }


    /**
     * _getConnection 获取redis连接
     * @author Louis
     * @return \Redis
     */
    private function _getConnection() : \Redis
    {
        if( !isset( self::$connPool[self::$current] ) )
        {
            $currentConfig = self::$config[self::$current];
            $conn = new \Redis();
            $conn->connect($currentConfig['host'],$currentConfig['port']);

            //连接密码
            if(isset($currentConfig['password']))
            {
                $conn->auth($currentConfig['password']);
            }

            //缓存库
            if(isset($currentConfig['db']))
            {
                $conn->select($currentConfig['db']);
            }
            self::$connPool[self::$current] = $conn;
        }
        return self::$connPool[self::$current];
    }


	/**
	 * Db 选择数据库
	 * @param int $dbName
	 * @return bool
	 */
	public function Db(int $dbName) : bool
    {
        return $this->_getConnection()->select( $dbName );
    }


	/**
	 * Set : write cache
	 * @param $key
	 * @param $val
	 * @return bool
	 */
	public function Set(string $key, string $val) : bool
    {
        return $this->_getConnection()->set( $key, $val );
    }


	/**
	 * Get : read cache
	 * @param string $key
	 * @return string|false
	 */
	public function Get(string $key)
    {
        return $this->_getConnection()->get($key);
    }


	/**
	 * Lpush : Adds the string values to the head (left) of the list. Creates the list if the key didn't exist.
     * If the key exists and is not a list, FALSE is returned
	 * @param string $queue
	 * @param mixed $val
	 * @return int|false
	 */
	public function Lpush(string $queue, string $val)
    {
        return $this->_getConnection()->lPush( $queue, $val );
    }


	/**
	 * Rpush : Adds the string values to the tail (right) of the list. Creates the list if the key didn't exist.
     * If the key exists and is not a list, FALSE is returned.
	 * @param string $queue
	 * @param mixed $val
	 * @return int|false
	 */
	public function Rpush(string $queue, string $val)
    {
        return $this->_getConnection()->rPush( $queue, $val );
    }


	/**
	 * Rpop : Returns and removes the last element of the list.
	 * @param  string $queue
	 * @return string|false
	 * Return value：STRING if command executed successfully BOOL FALSE in case of failure (empty list)
	 */
	public function Rpop(string $queue)
    {
        return $this->_getConnection()->rPop( $queue);
    }

	/**
	 * Lpop : Returns and removes the first element of the list.
	 * @param string $queue
	 * @return string|false
	 * Return value：STRING if command executed successfully BOOL FALSE in case of failure (empty list)
	 */
	public function Lpop(string $queue)
    {
        return $this->_getConnection()->lPop( $queue);
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
	public function BrPop(int $timeout, string ...$queue )
    {
        return $this->_getConnection() ->brPop ( $queue, $timeout );
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
	public function BlPop(int $timeout, string ...$queue)
    {
        return $this->_getConnection() ->blPop ( $queue, $timeout );
    }

	/**
	 * Hset hash table 写入
	 * @param string $key
	 * @param string $hashKey
	 * @param string $value
	 * @return mixed
	 *       LONG 1 if value didn't exist and was added successfully, 0 if the value was already present and was replaced, FALSE if there was an error.
	 */
	public function Hset(string $key, string $hashKey, string $value)
    {
        return $this->_getConnection() ->Hset ( $key, $hashKey, $value);
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
        return $this->_getConnection() ->hGet ( $key, $hashKey );
    }


	/**
	 * Hlen hashTable 长度
	 * @param string $key
	 * @return mixed
	 *     LONG the number of items in a hash, FALSE if the key doesn't exist or isn't a hash.
	 */
	public function Hlen(string $key)
    {
        return $this->_getConnection() ->hGet ( $key );
    }


	/**
	 * Ping
	 * @return mixed
	 * 		STRING: +PONG on success. Throws a RedisException object on connectivity error, as described above.
	 */
	public function Ping()
    {
        return $this->_getConnection() ->ping ();
    }


	/**
	 * close 关闭连接
	 * @return mixed
	 */
	public function close()
    {
        return $this->_getConnection() ->close();
    }

}