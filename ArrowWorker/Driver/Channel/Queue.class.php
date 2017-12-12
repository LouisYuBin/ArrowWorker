<?php

/**
 * User: Arrow
 * Date: 2017/9/22
 * Time: 12:51
 */

namespace ArrowWorker\Driver\Channel;

use ArrowWorker\Driver\Channel;


/**
 * Class Pipe  管道类
 * @package ArrowWorker\Driver\Channel
 */
class Queue extends Channel
{

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
     * @return Pipe
     */
    public static function Init(array $config, string $alias)
    {
        //存储配置
        if ( !isset( self::$config[$alias] ) )
        {
            self::$config[$alias] = $config;
        }

        //设置当前
        self::$current = $alias;

        if(!self::$instance)
        {
            self::$instance = new self($config);
        }
        //初始化（创建管道等）
        static::_init();

        return self::$instance;
    }


    /**
     * _initHandle  创建管道文件
     * @author Louis
     * @throws \Exception
     */
    private static function _init()
    {
        //如果已经创建并做了相关检测则直接跳过
        if( isset( static::$pool[self::$current] ) )
        {
            return;
        }

        $pathName = self::$config[self::$current]['path'];
		if (!file_exists($pathName))
		{
			throw new \Exception("path : {$pathName} does not exists.");
		}
		$key = ftok($pathName, 'A');
        static::$pool[self::$current] = msg_get_queue($key, static::mode);;
    }

    private function _getQueue()
	{
		return static::$pool[self::$current];
	}


    /**
     * Write  写入消息
     * @author Louis
     * @param string $message 要写如的消息
     * @param bool $isBlock 是否阻塞
     * @return bool|int
     */
    public function Write( string $message )
    {
		return msg_send( static::_getQueue(), 1, $message);
	}

    /**
     * Read 写消息
     * @author Louis
     * @param int $sequence 从队列什么位置开始读取消息
     * @return bool|string
     */
    public function Read(int $sequence)
    {
		$result = msg_receive(static::_getQueue(), 0, $message_type, 1024, $message, true, MSG_IPC_NOWAIT);
    	return $result ? $message : $result;
    }

    /**
     * Close 关闭打开的管道
     * @author Louis
     */
    public function Close()
    {
        foreach (self::$pool as $eachQueue)
        {
            msg_remove_queue($eachQueue);
        }
    }

    /**
     *__destruct
     */
    public function __destruct()
    {
		//todo
    }

}

