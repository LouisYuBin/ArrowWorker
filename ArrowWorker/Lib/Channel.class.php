<?php


namespace ArrowWorker\Lib;


/**
 * Class Channel
 * @package ArrowWorker\Lib
 */
class Channel
{
    /**
     * @var int
     */
    private $_size = 10;
    /**
     * @var \Swoole\Coroutine\Channel
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
        $this->_instance = new \Swoole\Coroutine\Channel($size);
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

    /**
     * @param int $timeout
     *
     * @return mixed
     */
    public function Pop(int $timeout=1)
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