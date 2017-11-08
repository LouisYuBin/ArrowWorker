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
    public function set($key,$val)
    {
        return $this -> getConnection() -> set( $key, $val );
    }

    //读取
    public function get($key)
    {
        return $this -> getConnection() -> get($key);
    }

    //写队列
    public function push($queue,$val)
    {
        return $this -> getConnection() ->lPush($queue,$val);
    }

    //读队列
    public function pop($queue)
    {
        return $this -> getConnection() ->rPop($queue);
    }

    //读队列
    public function close()
    {
        return $this -> getConnection() ->close();
    }

}