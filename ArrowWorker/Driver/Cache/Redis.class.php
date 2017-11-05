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
    static function init($config)
    {
        if(!self::$cacheObj)
        {
            self::$cacheObj = new self($config);
        }
        return self::$cacheObj;
    }

    //连接缓存
    private function connect()
    {
        if(!self::$CacheConn)
        {
            self::$CacheConn = new \Redis();
            self::$CacheConn -> connect(self::$config['host'],self::$config['port']);
            //缓存库
            if(isset(self::$config['db']))
            {
                self::$CacheConn -> select(self::$config['db']);
            }
            //连接密码
            if(isset(self::$config['password']))
            {
                self::$CacheConn -> auth(self::$config['password']);
            }
        }
    }

    //写入
    public function set($key,$val)
    {
        $this -> connect();
        return self::$CacheConn ->set($key,$val);
    }

    //读取
    public function get($key)
    {
        $this -> connect();
        return self::$CacheConn ->get($key);
    }

    //写队列
    public function push($queue,$val)
    {
        $this -> connect();
        return self::$CacheConn ->lPush($queue,$val);
    }

    //读队列
    public function pop($queue)
    {
        $this -> connect();
        return self::$CacheConn ->rPop($queue);
    }

    //读队列
    public function close()
    {
        $this -> connect();
        return self::$CacheConn ->close();
    }

}