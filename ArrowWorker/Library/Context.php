<?php
/**
 * By yubin at 2019-12-17 17:20.
 */

namespace ArrowWorker\Library;

use \Swoole\Coroutine as Co;

class Context
{
	
	public static function Set(string $key, $value)
	{
		Co::getContext()[$key] = $value;
	}
	
	public static function Get(string $key)
	{
		return Co::getContext()[$key];
	}
	
	
}