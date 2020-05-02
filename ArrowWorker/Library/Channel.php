<?php


namespace ArrowWorker\Library;


use ArrowWorker\Container;
use Swoole\Coroutine\Channel as SwChan;

/**
 * Class Channel
 * @package ArrowWorker\Library
 */
class Channel
{
    /**
     * @var int
     */
    private $size = 10;
    /**
     * @var SwChan
     */
    private $swChan;

    private $container;

    /**
     * @param Container $container
     * @param int $size
     * @return Channel
     */
    public static function Init(Container $container, int $size)
    {
        return new self($container, $size);
    }

    /**
     * Channel constructor.
     * @param Container $container
     * @param int $size
     */
    public function __construct(Container $container, int $size)
    {
        $this->container = $container;
        $this->size = $size;
        $this->swChan = $container->Make(SwChan::class, [$size]);
    }

    /**
     * @param     $data
     * @param int $timeout
     *
     * @return mixed
     */
    public function Push($data, $timeout = 1)
    {
        return $this->swChan->push($data, $timeout);
    }

    public function GetErrorCode()
    {
        return $this->swChan->errCode;
    }

    /**
     * @param float $timeout
     * @return mixed
     */
    public function Pop(float $timeout = 1)
    {
        return $this->swChan->pop($timeout);
    }

    /**
     * @return int
     */
    public function Length()
    {
        return $this->swChan->length();
    }

    /**
     * @return mixed
     */
    public function Close()
    {
        return $this->swChan->close();

    }

    /**
     * @return bool
     */
    public function IsEmpty()
    {
        return $this->swChan->isEmpty();
    }

    /**
     * @return bool
     */
    public function IsFull()
    {
        return $this->swChan->isFull();
    }

}