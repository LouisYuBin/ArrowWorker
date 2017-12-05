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

    const mode = 066;
    /**
     * @var
     */
    protected $read;

    /**
     * @var
     */
    protected $write;

    /**
     * Pipe constructor.
     * @param string $filename
     * @param int $mode
     * @param bool $block
     */
    public function __construct($fileName = '/tmp/simple-fork.pipe', $mode = 0666)
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

    /**
     * Init 初始化 对外提供
     * @author Louis
     * @param array $config
     * @param string $alias
     * @return Pipe
     */
    public function Init(array $config, string $alias)
    {
        //存储配置
        if ( !isset( self::$config[$alias] ) )
        {
            self::$config[$alias] = $config;
        }

        //设置当前
        self::$current = $alias;

        if(!self::$instance)
        {
            self::$instance = new self($config);
        }

        return self::$instance;
    }

    /**
     * _initHandle 创建管道文件爱呢
     * @author Louis
     * @param array $config
     * @throws \Exception
     */
    private function _initHandle(array $config)
    {
        if (!file_exists($config['name']) && !posix_mkfifo($config['name'], self::mode))
        {
            throw new \Exception("create pipe:{$config['name']} failed");
        }
        if (filetype($config['name']) != "fifo")
        {
            throw new \Exception("pipe:{$config['name']} is not a fifo file");
        }
    }

    /**
     * _initHandle 获取当前管道
     * @author Louis
     * @param array $config
     * @throws \Exception
     */
    public function GetHandle()
    {
        if( !isset( self::$pool[self::$current] ) )
        {
            self::$pool[self::$current] = $this -> _initHandle( self::$config[self::$current] );
        }
        return self::$pool[self::$current]['name'];
    }

    /**
     * Read
     * @author Louis
     * @param int $size
     * @param bool $isBlock
     * @return bool|string
     */
    public function Read(bool $isBlock=false)
    {
        $fifo = $this -> GetHandle();
        if ( !is_resource($fifo) )
        {
            $this->read = fopen($fifo['name'], 'r+');
            if (!is_resource($this->read))
            {
                throw new \Exception("open fifo:{$fifo['name']} file failed");
            }

            //是否阻塞
            $setBlock = stream_set_blocking($this->read, $isBlock);
            if (!$setBlock)
            {
                throw new \RuntimeException("stream_set_blocking failed");
            }
        }

        return fread($this->read, $fifo['size']);
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

