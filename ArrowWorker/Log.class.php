<?php
/**
 * User: louis
 * Time: 18-6-10 上午1:16
 */

namespace ArrowWorker;


use ArrowWorker\Driver\Cache\Redis;
use ArrowWorker\Driver\Channel\Queue;

/**
 * Class Log
 * @package ArrowWorker
 */
class Log
{
    /**
     * write log to file
     * @var string
     */
    const TO_FILE  = 'File';

    /**
     * write log to redis queue
     * @var string
     */
    const TO_REDIS = 'Redis';

    /**
     * write log to file and redis queue
     * @var string
     */
    const TO_ALL   = 'All';


    /**
     * default log timezone
     */
    const TIME_ZONE = 'UTC';


    /**
     * period for checkout log file size
     * @var int
     */
    const SIZE_CHECK_PERIOD = 10;

    /**
     * bufSize : log buffer size
     * @var int
     */
    private static $bufSize = 10240000;

    /**
     * msgSize : a single log size
     * @var int
     */
    private static $msgSize = 512;

    /**
     * directory for store log files
     * @var string
     */
    private static $baseDir =  APP_PATH.DIRECTORY_SEPARATOR.APP_RUNTIME_DIR.'/Log/';

    /**
     * write log to file
     * @var string
     */
    private static $_writeType = 'File';

    /**
     * host ip of redis
     * @var string
     */
    private static $host = '127.0.0.1';

    /**
     * port of redis
     * @var int
     */
    private static $port = 6379;

    /**
     * password of redis
     * @var string
     */
    private static $password = 'louis';

    /**
     * user name of queue
     * @var string
     */
    private static $userName = 'root';

    /**
     * queue of redis for log
     * @var string
     */
    private static $queue = 'ArrowLog';

    /**
     * Whether or not to close the log process
     * @var bool
     */
    private static $isTerminate = false;


    /**
     * logFileSize : single log file size
     * @var int
     */
    private static $logFileSize = 1073741824;

    /**
     * @var string
     */
    private static $logTimeZone='UTC';

    /**
     * @var
     */
    private static $_stdout;

    /**
     * @var
     */
    public static $StdoutFile;


    /**
     * redis instance
     * @var Redis
     */
    private static $redis;

    /**
     * log file resource
     * @var resource
     * */
    private static $file;

    /**
     * path of log file
     * @var string
     */
    private static $filePath = '';

    //日志等级，1:E_ERROR , 2:E_WARNING , 8:E_NOTICE , 2048:E_STRICT , 30719:all
    /**
     * @var int
     */
    private static $outputLevel = 30719;

    private static $_function   = '_writeToFile';



    /**
     * Init log process
     */
    public static function Init()
    {
        static::_checkExtension();
        static::_initConfig();
        static::_checkLogDir();
        static::_initHandler();
        static::_resetStd();
    }

    private static function _checkExtension()
    {
        if( !extension_loaded('sysvmsg') )
        {
            static::DumpExit('extension sysvmsg does not installed/loaded.');
        }
    }

    private static function _checkLogDir()
    {
        if( !is_dir(static::$baseDir) )
        {
            if( !mkdir(static::$baseDir) )
            {
                static::DumpExit('creating log directory failed.');
            }
        }
    }

    /**
     * _initHandler : init redis and file for log
     */
    private static function _initHandler()
    {
        switch( static::$_writeType)
        {
            case static::TO_REDIS:
                static::$redis = Redis::Init([
                    'host' => static::$host,
                    'port' => static::$port,
                    'password' => static::$password
                ],
                    'log'
                );
                static::$_function = '_writeToRedis';
                break;
            case static::TO_FILE;
                static::_initFile();
                static::$_function = '_writeToFile';
                break;
            case static::TO_ALL;
                static::$redis = Redis::Init([
                    'host' => static::$host,
                    'port' => static::$port,
                    'password' => static::$password
                ],
                    'log'
                );

                static::_initFile();
                static::$_function = '_writeToAll';
                break;
            default:
                static::_initFile();
                static::$_function = '_writeToFile';

        }
        static::_selectLogChan();

    }

    /**
     * _initFile :initialize log file handler
     */
    private static function _initFile()
    {
        for( $i=1; $i<3; $i++ )
        {
            static::$file = fopen(static::$filePath,'a');
            if( false !== static::$file )
            {
                break ;
            }
        }
    }

    /**
     * _resetLogFile : check log file and reset log file
     */
    private static function _resetLogFile()
    {
        clearstatcache(true,static::$filePath);
        $size = filesize(static::$filePath);
        if( $size===false )
        {
            static::Dump('get log file size error : '.static::$filePath);
            return ;
        }

        if( (int)$size < static::$logFileSize )
        {
            return;
        }
        
        if( !fclose(static::$file) )
        {
            static::Dump('close log file failed');
            return ;
        }

        static::Dump('starting rename log file ('.$size.'/'.static::$logFileSize.')');
        rename(static::$filePath, static::$baseDir.DIRECTORY_SEPARATOR.static::_getLogFileName().'_'.date('Y-m-d H:i:s').'.log');

        static::_initFile();

    }


    /**
     * _initConfig : init log configuration
     */
    private static function _initConfig()
    {
        $config = Config::Get('Log');
        if( false === $config )
        {
            return;
        }

        static::$bufSize = $config['bufSize'] ?? static::$bufSize;
        static::$baseDir = $config['baseDir'] ?? static::$baseDir;
        static::$_writeType = $config['type'] ?? static::$_writeType;
        static::$host = $config['host'] ?? static::$host;
        static::$port = $config['port'] ?? static::$port;
        static::$password = $config['password'] ?? static::$password;
        static::$userName = $config['userName'] ?? static::$userName;
        static::$queue    = $config['queue'] ?? static::$queue;
        static::$logFileSize = $config['fileSize'] ?? static::$logFileSize;
        static::$outputLevel = $config['errorLevel'] ??  static::$outputLevel;
        static::$logTimeZone = $config['timeZone'] ??  static::TIME_ZONE;
        static::$filePath   = static::$baseDir.DIRECTORY_SEPARATOR.static::_getLogFileName().'.log';
        static::$StdoutFile = static::$baseDir.DIRECTORY_SEPARATOR.'ArrowWorker.output';
        error_reporting((int)static::$outputLevel);
        date_default_timezone_set(self::$logTimeZone);

    }

    private static function _getLogFileName() : string
    {
        if ( is_array(APP_TYPE) )
        {
            return implode('_',APP_TYPE);
        }
        return APP_TYPE;
    }


    /**
     * Info write an information log
     * @param string $log
     */
    public static function Info(string $log)
    {
        static::_selectLogChan()->Write('I '.static::_getTime().' '.$log.PHP_EOL);
    }


    /**
     * Notice : write an notice log
     * @param string $log
     */
    public static function Notice(string $log)
    {
        static::_selectLogChan()->Write('N '.static::_getTime().' '.$log.PHP_EOL);
    }


    /**
     * Warning : write an warning log
     * @param string $log
     */
    public static function Warning(string $log)
    {
        static::_selectLogChan()->Write('W '.static::_getTime().' '.$log.PHP_EOL);
    }


    /**
     * Error : write and error log
     * @param string $log
     */
    public static function Error(string $log)
    {
        static::_selectLogChan()->Write('E '.static::_getTime().' '.$log.PHP_EOL);
    }


    /**
     * Dump : echo log to standard output
     * @param string $log
     */
    public static function Dump(string $log)
    {
        echo sprintf("%s - %s".PHP_EOL,static::_getTime(), $log);
    }

    /**
     * Dump : echo log to standard output
     * @param string $log
     */
    public static function DumpExit(string $log)
    {
        exit($log.PHP_EOL);
    }

    public static function Hint(string $log)
    {
        echo $log.PHP_EOL;
    }


    /**
     * _selectLogChan : select the log chan
     * @return Queue
     */
    private static function _selectLogChan() : Queue
    {
        return Queue::Init(
            [
                'msgSize' => static::$msgSize,
                'bufSize' => static::$bufSize
            ],
            'log'
        );
    }


    /**
     * _writeToRedis : write log to redis queue
     */
    private static function _writeToRedis()
    {
        $log = static::_selectLogChan()->Read();
        if( $log !== false )
        {
            usleep(100000);
           return ;
        }

        for($i=0; $i<3; $i++)
        {
            if( false !== static::$redis->Lpush(static::$queue, $log) )
            {
                break;
            }
        }
    }


    /**
     * _writeToFile : write log to file
     */
    private static function _writeToFile()
    {
        $log = static::_selectLogChan()->Read();
        if( $log === false )
        {
            usleep(100000);
            return ;
        }

        for($i=0; $i<3; $i++)
        {
            if( false!==fwrite(static::$file, $log) )
            {
                return ;
            }
        }
    }

    /**
     * _writeToAll : write log to both redis queue and file
     */
    private static function _writeToAll()
    {
        $log = static::_selectLogChan()->Read();
        if( $log === false )
        {
            usleep(100000);
            return ;
        }

        for($i=0; $i<3; $i++)
        {
            if( false!==fwrite(static::$file, $log) )
            {
                goto WriteRedis;
            }

            WriteRedis:
            if( false !== static::$redis->Lpush(static::$queue, $log) )
            {
                break;
            }
        }
    }

    /**
     * Start : start a log process
     */
    public static function Start()
    {
        static::_setSignalHandler();
        pcntl_alarm(static::SIZE_CHECK_PERIOD);
        $function = static::$_function;
        while( true )
        {
            if( static::$isTerminate )
            {
                static::_exit();
            }

            pcntl_signal_dispatch();
            static::$function();
            pcntl_signal_dispatch();
        }
    }

    /**
     * _exit : exit log process while there are no message in log queue
     */
    private static function _exit()
    {
        if( static::_selectLogChan()->Status()['msg_qnum']===0 )
        {
            static::DumpExit('Log queue status : '.json_encode(static::_selectLogChan()->Status()));
        }
    }

    /**
     * _getTime : get specified date format
     * @return false|string
     */
    private static function _getTime()
    {
        return date('Y-m-d H:i:s');
    }

    /**
     * _resetStd reset standard output and error log
     * @author Louis
     */
    private static function _resetStd()
    {
        global $STDOUT, $STDERR;
        static::$_stdout = fopen(static::$StdoutFile , "a");
        if ( is_resource(static::$_stdout))
        {
            fclose(STDOUT);
            fclose(STDERR);
            $STDOUT = fopen(static::$StdoutFile , 'a');
            $STDERR = fopen(static::$StdoutFile , 'a');
            return ;

        }
        else
        {
            die("ArrowWorker hint : can not open stdoutFile".PHP_EOL);
        }
    }

    /**
     * @param string $msg
     */
    private static function _outputToFile(string $msg)
    {
        fwrite( static::$_stdout, $msg);
    }

    /**
     * _setSignalHandler : set function for signal handler
     * @author Louis
     */
    private static function _setSignalHandler()
    {
        pcntl_signal(SIGALRM, array(__CLASS__, "signalHandler"),false);
        pcntl_signal(SIGTERM, array(__CLASS__, "signalHandler"),false);

        pcntl_signal(SIGCHLD, SIG_IGN,false);
        pcntl_signal(SIGQUIT, SIG_IGN,false);
    }


    /**
     * signalHandler : function for handle signal
     * @author Louis
     * @param int $signal
     */
    public static function signalHandler(int $signal)
    {
        if( $signal==SIGTERM  )
        {
            self::$isTerminate = true;
        }
        else if($signal==SIGALRM)
        {
            static::_resetLogFile();
            pcntl_alarm(static::SIZE_CHECK_PERIOD);
        }
    }

}