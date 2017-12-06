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

    private static $fifoMap = [];


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
     * _initHandle  创建管道文件
     * @author Louis
     * @throws \Exception
     */
    private function _initHandle()
    {
        //如果已经创建并做了相关检测则直接跳过
        if( isset( static::$fifoMap[self::$current] ) )
        {
            return;
        }
        $fifoName = self::$pool[self::$current]['path'];
        if (!file_exists(self::$config[self::$current]) && !posix_mkfifo($fifoName, self::mode))
        {
            throw new \Exception("create pipe:{$fifoName} failed");
        }
        if (filetype($fifoName) != "fifo")
        {
            throw new \Exception("pipe:{$fifoName} is not a fifo file");
        }
        static::$fifoMap[self::$current] = $fifoName;
    }

    /**
     * _getHandle 获取当前读/写管道 实例
     * @author Louis
     * @param string $alias  实例别名
     * @param bool $isBlock  是否阻塞
     * @param string $property 文件打开读/写属性
     * @return mixed
     * @throws \Exception
     */
    public function _getHandle(string $handleAlias, bool $isBlock, string $property)
    {
        $alias = self::$current.$handleAlias;
        if( !isset( self::$pool[$alias] ) )
        {
            $this->_initHandle( );
            $fifoPath = self::$config[$alias]['path'];

            self::$pool[$alias] = fopen( $fifoPath, $property );
            if (!is_resource(self::$pool[$alias]))
            {
                throw new \Exception("open fifo:{$fifoPath} file failed");
            }

            //是否阻塞
            if (!stream_set_blocking(self::$pool[$alias], $isBlock))
            {
                throw new \RuntimeException("pipe stream_set_blocking : $fifoPath failed");
            }

        }
        return self::$pool[$alias];
    }

    /**
     * GetWriteHandle 获取当前管道 写操作
     * @author Louis
     * @param bool $isBlock
     * @throws \Exception
     */
    public function GetWriteHandle(bool $isBlock=false )
    {
        $current = self::$current.__FUNCTION__;
        return $this->_getHandle($current, $isBlock, static::writeProperty);
    }

    /**
     * GetReadHandle 获取当前管道 写操作
     * @author Louis
     * @param bool $isBlock
     * @throws \Exception
     */
    public function GetReadHandle(bool $isBlock=false )
    {

    }

    /**
     * Write  写入消息
     * @author Louis
     * @param bool $isBlock 是否阻塞
     * @return bool|string
     */
    public function Write(string $message, bool $isBlock=false )
    {
        $handle = $this->_getHandle(__FUNCTION__, $isBlock, static::writeProperty);
        return fwrite( $handle, $message);
    }

    /**
     * Write 写消息
     * @author Louis
     * @param string $message
     * @param bool $isBlock
     * @return bool|int
     */
    public function Read(bool $isBlock=false)
    {
        $handle = $this->_getHandle(__FUNCTION__, $isBlock, static::readProperty);
        return fread( $handle, $message);
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

