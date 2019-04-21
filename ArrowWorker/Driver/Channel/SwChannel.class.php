<?php

/**
 * User: Arrow
 * Date: 2017/9/22
 * Time: 12:51
 */

namespace ArrowWorker\Driver\Channel;

use ArrowWorker\Driver\Channel;
use ArrowWorker\Log;


/**
 * Class Queue  队列类
 * @package ArrowWorker\Driver\Channel
 */
class SwChannel extends Channel
{

    /**
     * 打开方式
     */
    const mode = 0666;

    /**
     * Pipe constructor.
     * @param array $config
     */
    public function __construct(array $config)
    {
       //todo
    }

    /**
     * Init 初始化 对外提供
     * @author Louis
     * @param array $config
     * @param string $alias
     * @return SwChannel
     */
    public static function Init(array $config, string $alias)
    {
        //设置当前
        self::$current = $alias;

        //如果已经创建并做了相关检测则直接跳过
        if( isset( static::$pool[self::$current] ) )
        {
            return self::$instance;
        }

        //存储配置
        if ( !isset( self::$config[$alias] ) )
        {
            self::$config[$alias] = $config;
        }

        if( !self::$instance )
        {
            self::$instance = new self($config);
        }

        static::_initQueue();

        return static::$instance;
    }


    /**
     * _initHandle  create queue
     * @author Louis
     */
    private static function _initQueue()
    {
        try
        {
            $queue = new \Swoole\Coroutine\Channel(self::$config[self::$current]['bufSize']);
            var_dump($queue);
        }
        catch (\Exception $e)
        {
            Log::DumpExit('init \Swoole\Coroutine\Channel error : '.$e->getMessage());
        }

        static::$pool[self::$current] = $queue;
    }

    /**
     * _getQueue 获取队列
     * @author Louis
     * @param string $alias
     * @return mixed
     */
    private function _getQueue(string $alias='')
	{
	    if( isset(static::$pool[$alias]) )
        {
            return static::$pool[$alias];
        }

        var_dump(static::$pool[self::$current]);

		return static::$pool[self::$current];
	}

    /**
     * Write  写入消息
     * @author Louis
     * @param string $message 要写入的消息
     * @param string $chan channel name
     * @param int $msgType 消息类型
     * @return bool
     */
    public function Write( string $message, string $chan='', int $msgType=1 )
    {
        return static::_getQueue($chan)->push($message);
	}

    /**
     * Status  获取队列状态
     * @author Louis
     * @param string $alias 队列配置名
     * @return array
     */
    public function Status( string $alias='' )
    {
        return static::_getQueue($alias)->stats();

    }

    /**
     * Read 写消息
     * @author Louis
     * @param int $waitSecond seconds to wait while there is no message in channel
     * @param string $chan channel name to read from
     * @param int $msgType message type to be read
     * @return bool|string
     */
    public function Read(int $waitSecond=1, string $chan='', int $msgType=1)
    {
        var_dump('in read');
		return static::_getQueue($chan)->pop(1);
    }

    /**
     * Close 关闭管道
     * @author Louis
     */
    public static function Close()
    {
        foreach (static::$pool as $eachQueue)
        {
            unset($eachQueue);
        }
    }

    /**
     *__destruct
     */
    public function __destruct()
    {
		//$this->Close();
    }

}

