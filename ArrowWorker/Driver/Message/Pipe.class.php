<?php

/**
 * User: Arrow
 * Date: 2017/9/22
 * Time: 12:51
 */

namespace ArrowWorker\Driver\Message;

use ArrowWorker\Driver\Message;


/**
 * Class Pipe  管道类
 * @package ArrowWorker\Driver\Message
 */
class Pipe extends Message
{

    /**
     * @var
     */
    protected $read;

    /**
     * @var
     */
    protected $write;

    /**
     * @var string
     */
    protected $filename;

    /**
     * @var
     */
    protected $block;

    /**
     * Pipe constructor.
     * @param string $filename
     * @param int $mode
     * @param bool $block
     */
    public function __construct($fileName = '/tmp/simple-fork.pipe', $mode = 0666, $block = false)
    {
        if (!file_exists($fileName) && !posix_mkfifo($fileName, $mode))
        {
            throw new \RuntimeException("create pipe failed");
        }

        if (filetype($fileName) != "fifo")
        {
            throw new \RuntimeException("file exists and it is not a fifo file");
        }

        $this->filename = $fileName;
    }

    private function pipeInit(array $config)
    {
        if (!file_exists($config['fileName']) && !posix_mkfifo($config['fileName'], $config['mode']))
        {
            throw new \RuntimeException("create pipe failed");
        }

        if (filetype($config['fileName']) != "fifo")
        {
            throw new \RuntimeException("file exists and it is not a fifo file");
        }
    }

    public function GetMsgConnection()
    {
        if( !isset( self::$msgPool[self::$msgCurrent] ) )
        {
            self::$msgPool[self::$msgCurrent] = $this -> pipeInit( self::$config[self::$dbCurrent] );
        }
        return self::$msgPool[self::$msgCurrent];
    }

    /**
     * Read
     * @author Louis
     * @param int $size
     * @param bool $isBlock
     * @return bool|string
     */
    public function Read(int $size = 1024, bool $isBlock=false)
    {
        if ( !is_resource($this->read) )
        {
            $this->read = fopen($this->filename, 'r+');
            if (!is_resource($this->read))
            {
                throw new \RuntimeException("open file failed");
            }

            //是否阻塞
            $setBlock = stream_set_blocking($this->read, $isBlock);
            if (!$setBlock)
            {
                throw new \RuntimeException("stream_set_blocking failed");
            }
        }

        return fread($this->read, $size);
    }

    /**
     * Write
     * @author Louis
     * @param string $message
     * @param bool $isBlock
     * @return bool|int
     */
    public function Write(string $message, bool $isBlock=false)
    {
        if ( !is_resource($this->write) )
        {
            $this->write = fopen($this->filename, 'w+');
            if ( !is_resource($this->write) )
            {
                throw new \RuntimeException("open file failed");
            }

            //
            $setBlock = stream_set_blocking($this->write, $isBlock);
            if ( !$setBlock )
            {
                throw new \RuntimeException("stream_set_blocking failed");
            }
        }

        return fwrite($this->write, $message);
    }

    /**
     * Close
     * @author Louis
     */
    public function Close()
    {
        if ( is_resource( $this->read ) )
        {
            fclose( $this->read );
        }
        if ( is_resource($this->write) )
        {
            fclose( $this->write );
        }
    }

    /**
     *
     */
    public function __destruct()
    {
        $this->close();
    }

    /**
     * Remove
     * @author Louis
     * @return bool
     */
    public function Remove()
    {
        return unlink( $this->filename );
    }
}

