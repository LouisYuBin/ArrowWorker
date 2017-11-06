<?php

/**
 * User: Arrow
 * Date: 2017/9/22
 * Time: 12:51
 */

namespace ArrowWorker\Driver\Message;

use ArrowWorker\Driver\Message as Msg;



class Pipe extends Msg
{

    protected $read;

    protected $write;

    protected $filename;

    protected $block;

    public function __construct($filename = '/tmp/simple-fork.pipe', $mode = 0666, $block = false)
    {
        if (!file_exists($filename) && !posix_mkfifo($filename, $mode))
        {
            throw new \RuntimeException("create pipe failed");
        }

        if (filetype($filename) != "fifo")
        {
            throw new \RuntimeException("file exists and it is not a fifo file");
        }

        $this->filename = $filename;
        $this->block    = $block;
    }

    public function setBlock($block = true)
    {
        if (is_resource($this->read))
        {
            $set = stream_set_blocking($this->read, $block);
            if (!$set)
            {
                throw new \RuntimeException("stream_set_blocking failed");
            }
        }

        if (is_resource($this->write))
        {
            $set = stream_set_blocking($this->write, $block);
            if (!$set)
            {
                throw new \RuntimeException("stream_set_blocking failed");
            }
        }

        $this->block = $block;
    }

    public function read($size = 1024)
    {
        if (!is_resource($this->read))
        {
            $this->read = fopen($this->filename, 'r+');
            if (!is_resource($this->read))
            {
                throw new \RuntimeException("open file failed");
            }
            if ( !$this->block )
            {
                $set = stream_set_blocking($this->read, false);
                if (!$set)
                {
                    throw new \RuntimeException("stream_set_blocking failed");
                }
            }
        }

        return fread($this->read, $size);
    }

    public function write($message)
    {
        if ( !is_resource($this->write) )
        {
            $this->write = fopen($this->filename, 'w+');
            if ( !is_resource($this->write) )
            {
                throw new \RuntimeException("open file failed");
            }
            if (!$this->block)
            {
                $set = stream_set_blocking($this->write, false);
                if ( !$set )
                {
                    throw new \RuntimeException("stream_set_blocking failed");
                }
            }
        }

        return fwrite($this->write, $message);
    }

    public function close()
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

    public function __destruct()
    {
        $this->close();
    }

    public function remove()
    {
        return unlink( $this->filename );
    }
}

