<?php
/**
 * By yubin at 2019-12-11 11:55.
 */

namespace ArrowWorker\Library;

use Swoole\Lock;


class Locker
{
	private $lock;
	
	private static $instance;
	
	private function __construct(int $type)
	{
		$this->lock = new Lock($type) ;
	}
	
	public static function Init(int $type=SWOOLE_MUTEX)
	{
		if( self::$instance instanceof Locker )
		{
			return self::$instance;
		}
		self::$instance = new self($type);
		return self::$instance;
	}
	
	public function Lock()
	{
		return $this->lock->lock();
	}
	
	public function Unlock()
	{
		return $this->lock->unlock();
	}
	
	public function LockRead()
	{
		return $this->lock->lock_read();
	}
	
	public function LockWait(float $time)
	{
		return $this->lock->lockwait($time);
	}
	
}