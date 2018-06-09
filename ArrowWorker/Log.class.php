<?php
/**
 * User: louis
 * Time: 18-6-10 上午1:16
 */

namespace ArrowWorker;


use ArrowWorker\Driver\Channel\Queue;

class Log
{
    private static $bufSize = 10240000;

    private static $msgSize = 512;

    private static $baseDir =  APP_PATH.DIRECTORY_SEPARATOR.APP_RUNTIME_DIR.'/Log/';

    private static $timeCache = [];


    public static function Init()
    {
        $config = Config::App('Log');
        if( false === $config )
        {
            return;
        }
        static::$bufSize = $config['bufSize'] ?? static::$bufSize;
        static::$baseDir = $config['baseDir'] ?? static::$baseDir;
        static::_resetStd();
    }


    public static function Info(string $log)
    {
        static::_selectLogChan()->Write('[Info '.static::_getTime().'] '.$log);
    }

    public static function Notice(string $log)
    {
        static::_selectLogChan()->Write('[Notice '.static::_getTime().'] '.$log);
    }

    public static function Warning(string $log)
    {
        static::_selectLogChan()->Write('[Warning '.static::_getTime().'] '.$log);
    }

    public static function Error(string $log)
    {
        static::_selectLogChan()->Write('[Error '.static::_getTime().'] '.$log);
    }


    public static function WriteLogQueue()
    {
        static::_selectLogChan()->Read();
    }

    private function _selectLogChan()
    {
        return Queue::Init(
            [
            'msgSize' => static::$msgSize,
            'bufSize' => static::$bufSize
            ],
            'log'
        );
    }

    public static function WriteLogFile()
    {
        $log = static::_selectLogChan()->Read();
    }

    private static function _getTime()
    {
        return date('Y-m-d H:i:s');
    }

    /**
     * _resetStd 重置标准输入输出
     * @author Louis
     */
    private static function _resetStd()
    {
        global $STDOUT, $STDERR;
        $output = static::$baseDir.DIRECTORY_SEPARATOR.'ArrowWorker.output';
        $handle = fopen($output, "a");
        if ($handle)
        {
            unset($handle);
            fclose(STDOUT);
            fclose(STDERR);
            $STDOUT = fopen($output, 'a');
            $STDERR = fopen($output, 'a');
        }
        else
        {
            die("ArrowWorker hint : can not open stdoutFile");
        }
    }

}