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
     * period for checkout log file size
     * @var int
     */
    const SIZE_CHECK_PERIOD = 60;

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
    private static $writeType = 'File';

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
    private static $outputLevel = 30719;


    /**
     * Init log process
     */
    public static function Init()
    {
        static::_initConfig();
        static::_resetStd();
        static::_initHandler();
    }

    /**
     * _initHandler : init redis and file for log
     */
    private static function _initHandler()
    {
        if(static::$writeType==static::TO_REDIS)
        {
            static::$redis = Redis::Init([
                'host' => static::$host,
                'port' => static::$port,
                'password' => static::$password
            ],
                'log'
            );
        }
        else if( static::$writeType==static::TO_FILE )
        {
            static::_initFile();
        }
        else if( static::$writeType==static::TO_ALL )
        {
            static::$redis = Redis::Init([
                'host' => static::$host,
                'port' => static::$port,
                'password' => static::$password
            ],
                'log'
            );

            static::_initFile();
        }
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
        $size = filesize(static::$filePath);
        if( $size===false )
        {
            static::Error('get log file size error : '.static::$filePath);
            return ;
        }
        
        if( (int)$size < static::$logFileSize )
        {
            return;
        }
        
        if( !fclose(static::$file) )
        {
            return ;
        }

        static::Info('starting rename log file ');
        rename(static::$filePath, date('Y-m-d H:i:s'));

        static::_initFile();

    }


    /**
     * _initConfig : init log configuration
     */
    private static function _initConfig()
    {
        $config = Config::App('Log');
        if( false === $config )
        {
            return;
        }

        static::$bufSize = $config['bufSize'] ?? static::$bufSize;
        static::$baseDir = $config['baseDir'] ?? static::$baseDir;
        static::$writeType = $config['type'] ?? static::$writeType;
        static::$host = $config['host'] ?? static::$host;
        static::$port = $config['port'] ?? static::$port;
        static::$password = $config['password'] ?? static::$password;
        static::$userName = $config['userName'] ?? static::$userName;
        static::$queue    = $config['queue'] ?? static::$queue;
        static::$filePath = static::$baseDir.DIRECTORY_SEPARATOR.APP_TYPE.'.log';
        static::$logFileSize = $config['fileSize'] ?? static::$logFileSize;
        static::$outputLevel = $config['errorLevel'] ??  static::$outputLevel;

        //设置运行日志级别
        error_reporting((int)static::$outputLevel);

    }


    /**
     * Info write an information log
     * @param string $log
     */
    public static function Info(string $log)
    {
        static::_selectLogChan()->Write('[Info '.static::_getTime().'] '.$log.PHP_EOL);
    }


    /**
     * Notice : write an notice log
     * @param string $log
     */
    public static function Notice(string $log)
    {
        static::_selectLogChan()->Write('[Notice '.static::_getTime().'] '.$log.PHP_EOL);
    }


    /**
     * Warning : write an warning log
     * @param string $log
     */
    public static function Warning(string $log)
    {
        static::_selectLogChan()->Write('[Warning '.static::_getTime().'] '.$log.PHP_EOL);
    }


    /**
     * Error : write and error log
     * @param string $log
     */
    public static function Error(string $log)
    {
        static::_selectLogChan()->Write('[Error '.static::_getTime().'] '.$log.PHP_EOL);
    }


    /**
     * Dump : echo log to standard output
     * @param string $log
     */
    public static function Dump(string $log)
    {
        echo sprintf("%s - %s".PHP_EOL,date('Y-m-d H:i:s'), $log);
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
        while( 1 )
        {
            if( static::$isTerminate )
            {
                static::_exit();
            }

            pcntl_signal_dispatch();
            if( static::$writeType==static::TO_FILE )
            {
                static::_writeToFile();
            }
            else if( static::$writeType==static::TO_REDIS )
            {
                static::_writeToRedis();
            }
            else if( static::$writeType==static::TO_ALL )
            {
                static::_writeToAll();
            }
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
            static::Dump('log process exited');
            exit(0);
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

    /**
     * _setSignalHandler : set function for signal handler
     * @author Louis
     */
    private static function _setSignalHandler()
    {
        pcntl_signal(SIGUSR1, array(__CLASS__, "signalHandler"),false);
        pcntl_signal(SIGALRM, array(__CLASS__, "signalHandler"),false);
        pcntl_signal(SIGTERM, array(__CLASS__, "signalHandler"),false);
    }


    /**
     * signalHandler : function for handle signal
     * @author Louis
     * @param int $signal
     */
    public static function signalHandler(int $signal)
    {
        static::Info('got signal : '.$signal);
        if( $signal==SIGUSR1 || $signal==SIGTERM  )
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