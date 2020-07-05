<?php
/**
 * By yubin at 2019-12-11 11:55.
 */

namespace ArrowWorker\Library;

use Swoole\Lock;


/**
 * Class Locker
 * @package ArrowWorker\Library
 */
class Locker
{
    /**
     * @var Lock
     */
    private $lock;

    /**
     * @var
     */
    private static $instance;

    /**
     * Locker constructor.
     * @param int $type
     */
    private function __construct(int $type)
    {
        $this->lock = new Lock($type);
    }

    /**
     * @param int $type
     * @return Locker
     */
    public static function Init(int $type = SWOOLE_MUTEX):self
    {
        if (self::$instance instanceof self) {
            return self::$instance;
        }
        self::$instance = new self($type);
        return self::$instance;
    }

    /**
     * @return mixed
     */
    public function Lock()
    {
        return $this->lock->lock();
    }

    /**
     * @return mixed
     */
    public function Unlock()
    {
        return $this->lock->unlock();
    }

    /**
     * @return mixed
     */
    public function LockRead()
    {
        return $this->lock->lock_read();
    }

    /**
     * @param float $time
     * @return mixed
     */
    public function LockWait(float $time)
    {
        return $this->lock->lockwait($time);
    }

}