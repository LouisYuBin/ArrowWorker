<?php
/**
 * By yubin at 2020/7/3 5:27 上午.
 */

namespace ArrowWorker\Log\Buffer;


/**
 * Class Buffer
 * @package ArrowWorker\Log\Buffer
 */
class Buffer
{

    /**
     * @var int
     */
    private int $lastReadTime = 0;

    /**
     * @var int
     */
    private int $currentBufSize = 0;

    /**
     * @var int
     */
    private int $maxBufTime = 3;


    /**
     * @var int
     */
    private int $maxBufSize = 3;

    /**
     * @var string
     */
    private string $content = '';

    /**
     * Buffer constructor.
     * @param int $maxBufTime
     * @param int $maxBufSize
     */
    public function __construct(int $maxBufTime, int $maxBufSize)
    {
        $this->maxBufSize = $maxBufSize;
        $this->maxBufTime = $maxBufTime;
    }


    /**
     * @return bool
     */
    public function isWritable(): bool
    {
        return time() - $this->lastReadTime < $this->maxBufTime && $this->currentBufSize < $this->maxBufSize;
    }

    /**
     * @param string $content
     */
    public function write(string $content): void
    {
        $this->content        .= $content;
        $this->currentBufSize += strlen($content);
    }

    /**
     * @return string
     */
    public function read(): string
    {
        if(0===strlen($this->content)) {
            return '';
        }

        $this->lastReadTime   = time();
        $this->currentBufSize = 0;
        return $this->content;
    }

}