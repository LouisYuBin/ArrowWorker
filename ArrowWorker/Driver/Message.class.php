<?php


/**
 * Class Message
 */
class Message
{

	/**
	 * 消息实例连接池
	 * @var array
	 */
	protected static $msgPool = [];

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
	protected static $MsgCurrent = '';

	/**
	 * Message constructor.
	 */
	private function __construct()
	{
		//todo
	}

}