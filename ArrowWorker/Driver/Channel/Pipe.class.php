<?php

/**
 * User: Arrow
 * Date: 2017/9/22
 * Time: 12:51
 */

namespace ArrowWorker\Driver\Channel;

use ArrowWorker\Driver\Channel;


/**
 * Class Pipe  管道类
 * @package ArrowWorker\Driver\Channel
 */
class Pipe extends Channel
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
    public static function Init(array $config, string $alias)
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
    private function _init()
    {
        //如果已经创建并做了相关检测则直接跳过
        if( isset( static::$fifoMap[self::$current] ) )
        {
            return;
        }

        $fifoName = self::$config[self::$current]['path'];
        if (!file_exists($fifoName) && !posix_mkfifo($fifoName, self::mode))
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
            $this->_init();
            $fifoPath = self::$config[static::$current]['path'];
            self::$pool[$alias] = fopen( $fifoPath, $property );
            if (!is_resource(self::$pool[$alias]))
            {
                throw new \Exception("open fifo:{$fifoPath} file failed");
            }
        }
        //是否阻塞
        if (!stream_set_blocking(self::$pool[$alias], $isBlock))
        {
            throw new \RuntimeException("pipe stream_set_blocking : $fifoPath failed");
        }
        return self::$pool[$alias];
    }

    /**
     * Write  写入消息
     * @author Louis
     * @param string $message 要写如的消息
     * @param bool $isBlock 是否阻塞
     * @return bool|int
     */
    public function Write( string $message )
    {
        $specifiedMsg = str_pad($message,static::$config[ static::$current ]['size']);
        $handle = $this->_getHandle(__FUNCTION__,false, static::writeProperty);
        return fwrite( $handle, $specifiedMsg);
    }

    /**
     * Write 写消息
     * @author Louis
     * @param bool $isBlock 是否以阻塞模式读取
     * @return bool|string
     */
    public function Read(bool $isBlock=false)
    {
        $handle = $this->_getHandle(__FUNCTION__, $isBlock, static::readProperty);
        $result = fread( $handle, static::$config[ static::$current ]['size'] );
        return $result ? trim($result) : $result;
    }

    /**
     * Close 关闭打开的管道
     * @author Louis
     */
    public function Close()
    {
        foreach (self::$pool as $eachPipe)
        {
            fclose($eachPipe);
        }
    }

    /**
     *__destruct
     */
    public function __destruct()
    {
        $this->Close();
        $this->Remove();
    }

    /**
     * Remove
     * @author Louis
     * @return bool
     */
    public function Remove()
    {
        foreach (self::$config as $eachConfig)
        {
            if( file_exists( $eachConfig['path'] ) )
            {
                @unlink( $eachConfig['path'] );
            }
        }
    }
}

