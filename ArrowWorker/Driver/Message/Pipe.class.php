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
    const readProperty  = "r+";
    const writeProperty = "w+";
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
     * @param array $config
     */
    public function __construct(array $config)
    {
       //todo
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
     * _initHandle 创建管道文件
     * @author Louis
     * @param string $alias
     * @throws \Exception
     */
    private function _initHandle(string $alias = '')
    {
        $fifoName = empty($alias) ? self::$pool[self::$current]['path'] : self::$pool[$alias]['path'];
        if (!file_exists(self::$config[self::$current]) && !posix_mkfifo($fifoName, self::mode))
        {
            throw new \Exception("create pipe:{$fifoName} failed");
        }
        if (filetype($fifoName) != "fifo")
        {
            throw new \Exception("pipe:{$fifoName} is not a fifo file");
        }
    }

    /**
     * _initHandle 获取当前管道
     * @author Louis
     * @param array $config
     * @throws \Exception
     */
    public function GetWriteHandle(bool $isBlock=false, string $alias='' )
    {
        $current = empty($alias) ? self::$current : $alias;
        $current = $current.__FUNCTION__;

        if( !isset( self::$pool[$current] ) )
        {
            $this->_initHandle( self::$config[$current] );
            $fifoPath = self::$config[$current]['path'];

            self::$pool[$current] = fopen( $fifoPath, static::writeProperty );
            if (!is_resource(self::$pool[$current]))
            {
                throw new \Exception("open fifo:{$fifoPath} file failed");
            }

            //是否阻塞
            if (!stream_set_blocking(self::$pool[$current], $isBlock))
            {
                throw new \RuntimeException("pipe stream_set_blocking : $fifoPath failed");
            }

        }
        return self::$pool[$current];
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
        return fread( $this -> GetWriteHandle(), $fifo['size'] );
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
            $this->write = fopen($this->filename, static::writeProperty);
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

