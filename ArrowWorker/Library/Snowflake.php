<?php

namespace ArrowWorker\Library;

class Snowflake
{
    /**
     * 起始时间戳，毫秒
     */
    const EPOCH = 1546300800000;

    /**
     * 序号部分12位
     */
    const SEQUENCE_LEN = 12;

    /**
     *  -1 ^ ( -1 << self::SEQUENCE_BITS )序号最大值
     */
    const SEQUENCE_MAX = 4095;

    /**
     * 节点部分10位
     */
    const WORKER_LEN = 10;

    /**
     * -1 ^ ( -1 << self::WORKER_BITS )节点最大数值
     */
    const WORKER_MAX = 1023;

    /**
     * 时间戳部分左偏移量
     */
    const TIME_SHIFT = self::WORKER_LEN + self::SEQUENCE_LEN;

    /**
     * 节点部分左偏移量
     */
    const WORKER_SHIFT = self::SEQUENCE_LEN;

    /**
     * 上次ID生成时间戳
     * @var int
     */
    protected $timestamp = 0;

    /**
     * 节点ID
     * @var int
     */
    protected $workerId = 1;

    /**
     * 时间戳内id序号
     * @var int
     */
    protected $sequence = 0;

    /**
     * @var Locker
     */
    protected $lock;

    public function __construct(int $workerId)
    {
        $this->timestamp = 0;
        $this->workerId = ($workerId < 0 || $workerId > self::WORKER_MAX) ? 1 : $workerId;
        $this->sequence = 0;
        $this->lock = Locker::Init();
    }

    /**
     * @return int
     */
    public function GenerateId()
    {
        $this->lock->Lock();
        $this->initSequence();
        $id = (($this->timestamp - self::EPOCH) << self::TIME_SHIFT) |
            ($this->workerId << self::WORKER_SHIFT) |
            $this->sequence;
        $this->lock->Unlock();
        return $id;
    }

    private function initSequence()
    {
        $currentTimestamp = $this->GetTimestamp();
        if ($this->timestamp == $currentTimestamp) {
            $this->sequence++;
            if ($this->sequence > self::SEQUENCE_MAX) {
                $this->timestamp = ++$this->timestamp;
                $this->sequence = 0;
            }
        } else {
            $this->timestamp = $currentTimestamp;
            $this->sequence = 1;
        }
        return;
    }

    /**
     * @return float
     */
    public function GetTimestamp(): float
    {
        return floor(microtime(true) * 1000);
    }

}