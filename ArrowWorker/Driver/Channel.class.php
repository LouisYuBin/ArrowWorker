<?php
namespace ArrowWorker\Driver;

use ArrowWorker\Log;
use ArrowWorker\Driver\Channel\Queue;
/**
 * Class Message
 */
class Channel
{

	/**
	 * 消息实例连接池
	 * @var array
	 */
	protected static $pool = [];

	/**
	 * 消息配置
	 * @var array
	 */
	protected static $config = [];

    /**
     * Init 初始化 对外提供
     * @author Louis
     * @param array $config
     * @param string $alias
     * @return Queue
     */
    public static function Init(array $config, string $alias)
    {
        //如果已经创建并做了相关检测则直接跳过
        if( isset( static::$pool[$alias] ) )
        {
            return static::$pool[$alias];
        }

        static::$pool[$alias] = new Queue($config,$alias);

        return static::$pool[$alias];
    }

    /**
     * Close 关闭管道
     * @author Louis
     */
    public static function Close()
    {
        foreach (static::$pool as $eachQueue)
        {
            Log::Dump("msg_remove_queue result : ".$eachQueue->Close());
        }
    }

}