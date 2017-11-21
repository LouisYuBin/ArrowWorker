<?php
/**
 * User: Arrow
 * Date: 2016/8/1
 * Time: 19:53
 */

namespace ArrowWorker\Driver\Cache;
use ArrowWorker\Driver\Cache as cache;


class Redis extends cache
{
    //初始化数据库连接类
    static function init($config, $alias)
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

    //连接缓存
    private function getConnection()
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

    //写入
    public function Db($dbName)
    {
        return $this -> getConnection() -> select( $dbName );
    }

    //写入
    public function Set($key,$val)
    {
        return $this -> getConnection() -> set( $key, $val );
    }

    //读取
    public function Get($key)
    {
        return $this -> getConnection() -> get($key);
    }

    //写队列(左)
    public function Lpush($queue,$val)
    {
        return $this -> getConnection() ->lPush( $queue, $val );
    }

    //写队列(右)
    public function Rpush($queue,$val)
    {
        return $this -> getConnection() ->rPush( $queue, $val );
    }

    //读队列(右)
    /*
     Parameters
        key
    Return value
        STRING if command executed successfully BOOL FALSE in case of failure (empty list)
    */
    public function Rpop($queue)
    {
        return $this -> getConnection() ->rPop( $queue);
    }

    //读队列(左)
    /*
     Parameters
        key
    Return value
        STRING if command executed successfully BOOL FALSE in case of failure (empty list)
    */
    public function Lpop($queue)
    {
        return $this -> getConnection() ->lPop( $queue);
    }

    //读队列(右)(阻塞模式)
    /*
    Parameters
        ARRAY Array containing the keys of the lists INTEGER Timeout Or STRING Key1 STRING Key2 STRING Key3 ... STRING Keyn INTEGER Timeout
    Return value
        ARRAY array('listName', 'element')
    */
    public function BrPop($queue, $timeout)
    {
        return $this -> getConnection() ->brPop ( $queue, $timeout );
    }

    //读队列(左)(阻塞模式)
    /*
    Parameters
        ARRAY Array containing the keys of the lists INTEGER Timeout Or STRING Key1 STRING Key2 STRING Key3 ... STRING Keyn INTEGER Timeout
    Return value
        ARRAY array('listName', 'element')
    */
    public function BlPop( $queue, $timeout )
    {
        return $this -> getConnection() ->blPop ( $queue, $timeout );
    }

    //hashTable 写入
    /*
    Parameters
        key hashKey value
    Return value
        LONG 1 if value didn't exist and was added successfully, 0 if the value was already present and was replaced, FALSE if there was an error.
    */
    public function Hset( $key, $hashKey, $value )
    {
        return $this -> getConnection() ->Hset ( $key, $hashKey, $value);
    }

    //hashTable 读取
    /*
     Parameters
        key hashKey
     Return value
        STRING The value, if the command executed successfully BOOL FALSE in case of failure
     */
    public function Hget( $key, $hashKey )
    {
        return $this -> getConnection() ->hGet ( $key, $hashKey );
    }

    //hashTable 长度
    /*
    Parameters
        key
    Return value
        LONG the number of items in a hash, FALSE if the key doesn't exist or isn't a hash.
    */
    public function Hlen( $key )
    {
        return $this -> getConnection() ->hGet ( $key );
    }

    /*
     Parameters
        (none)
    Return value
        STRING: +PONG on success. Throws a RedisException object on connectivity error, as described above.
     */
    public function Ping()
    {
        return $this -> getConnection() ->ping ();
    }

    //关闭连接
    public function close()
    {
        return $this -> getConnection() ->close();
    }

}