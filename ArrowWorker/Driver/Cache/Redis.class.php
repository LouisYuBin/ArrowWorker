<?php
/**
 * User: Arrow
 * Date: 2016/8/1
 * Time: 19:53
 */

namespace ArrowWorker\Driver\Cache;

use ArrowWorker\Log;


/**
 * Class Redis
 * @package ArrowWorker\Driver\Cache
 */
class Redis implements Cache
{


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
                Log::Warning( "connect redis failed, error message : ".$this->_conn->getLastError()." config : ".json_encode($this->_config), self::LOG_NAME );
                return false;
            }
        }
        catch (\RedisException $e)
        {
            Log::Warning( "connect redis failed, error message : ".$e->getMessage()." config : ".json_encode($this->_config), self::LOG_NAME );
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

    public function Init(array $config)
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
         $this->_conn->select( $dbName );
    }


	/**
	 * Set : write cache
	 * @param $key
	 * @param $val
	 * @return bool
	 */
	public function Set(string $key, string $val) : bool
    {
        return $this->_conn->set( $key, $val );
    }


	/**
	 * Get : read cache
	 * @param string $key
	 * @return string|false
	 */
	public function Get(string $key)
    {
        return $this->_conn->get($key);
    }


	/**
	 * Lpush : Adds the string values to the head (left) of the list. Creates the list if the key didn't exist.
     * If the key exists and is not a list, FALSE is returned
	 * @param string $queue
	 * @param mixed $val
	 * @return int|false
	 */
	public function LPush(string $queue, string $val)
    {
        return $this->_conn->lPush( $queue, $val );
    }


	/**
	 * Rpush : Adds the string values to the tail (right) of the list. Creates the list if the key didn't exist.
     * If the key exists and is not a list, FALSE is returned.
	 * @param string $queue
	 * @param mixed $val
	 * @return int|false
	 */
	public function RPush(string $queue, string $val)
    {
        return $this->_conn->rPush( $queue, $val );
    }


	/**
	 * Rpop : Returns and removes the last element of the list.
	 * @param  string $queue
	 * @return string|false
	 * Return value：STRING if command executed successfully BOOL FALSE in case of failure (empty list)
	 */
	public function RPop(string $queue)
    {
        return $this->_conn->rPop( $queue);
    }

	/**
	 * Lpop : Returns and removes the first element of the list.
	 * @param string $queue
	 * @return string|false
	 * Return value：STRING if command executed successfully BOOL FALSE in case of failure (empty list)
	 */
	public function LPop(string $queue)
    {
        return $this->_conn->lPop( $queue);
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
        return $this->_conn->brPop ( $queue, $timeout );
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
        return $this->_conn->blPop ( $queue, $timeout );
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
        return $this->_conn->Hset ( $key, $hashKey, $value);
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
        return $this->_conn->hGet ( $key, $hashKey );
    }


	/**
	 * Hlen hashTable 长度
	 * @param string $key
     * @param string $hashKey
	 * @return mixed
	 *     LONG the number of items in a hash, FALSE if the key doesn't exist or isn't a hash.
	 */
	public function Hlen(string $key, string $hashKey)
    {
        return $this->_conn->hGet ( $key, $hashKey);
    }


	/**
	 * Ping
	 * @return mixed
	 * 		STRING: +PONG on success. Throws a RedisException object on connectivity error, as described above.
	 */
	public function Ping()
    {
        return $this->_conn->ping ();
    }


	/**
	 * close 关闭连接
	 * @return mixed
	 */
	public function close()
    {
        return $this->_conn->close();
    }

}