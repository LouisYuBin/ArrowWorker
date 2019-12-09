<?php


namespace ArrowWorker\Library;


use \Swoole\Coroutine\Channel as SwChan;

/**
 * Class Channel
 * @package ArrowWorker\Library
 */
class Channel
{
    /**
     * @var int
     */
    private $_size = 10;
    /**
     * @var SwChan
     */
    private $_instance;

    /**
     * @param int $size
     *
     * @return Channel
     */
    public static function Init(int $size)
    {
        return new self($size);
    }

    /**
     * Channel constructor.
     *
     * @param int $size
     */
    private function __construct(int $size)
    {
        $this->_size = $size;
        $this->_instance = new SwChan($size);
    }

    /**
     * @param     $data
     * @param int $timeout
     *
     * @return mixed
     */
    public function Push($data, $timeout=1)
    {
        return $this->_instance->push($data, $timeout);
    }

    public function GetErrorCode()
    {
        return $this->_instance->errCode;
    }

    /**
     * @param float $timeout
     * @return mixed
     */
    public function Pop(float $timeout=1)
    {
        return $this->_instance->pop($timeout);
    }

    /**
     * @return int
     */
    public function Length()
    {
        return $this->_instance->length();
    }

    /**
     * @return mixed
     */
    public function Close()
    {
        return $this->_instance->close();

    }

    /**
     * @return bool
     */
    public function IsEmpty()
    {
        return $this->_instance->isEmpty();
    }

    /**
     * @return bool
     */
    public function IsFull()
    {
        return $this->_instance->isFull();
    }

}