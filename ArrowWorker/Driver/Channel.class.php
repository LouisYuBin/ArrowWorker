<?php
namespace ArrowWorker\Driver;

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
	 * 单例模式对象
	 * @var
	 */
	protected static $instance;

	/**
	 * 消息配置
	 * @var array
	 */
	protected static $config = [];

	/**
	 * @var string
	 */
	protected static $current = '';


    protected static $chanFileDir = APP_PATH.DIRECTORY_SEPARATOR.APP_RUNTIME_DIR.DIRECTORY_SEPARATOR.'Chan/';


    /**
	 * Message constructor.
	 */
	private function __construct()
	{
		//todo
	}

}