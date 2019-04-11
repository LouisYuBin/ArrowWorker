<?php
/**
 * User: louis
 * Time: 18-6-10 上午1:16
 */

namespace ArrowWorker;


use ArrowWorker\Driver\Cache\Redis;
use ArrowWorker\Driver\Channel\Queue;
use ArrowWorker\Driver\Channel\SwChannel;

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
    private static $msgSize = 65535;

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
        static::_initLogSetting();
        static::_initHandler();
        static::_resetStd();
    }

    private static function _checkExtension()
    {
        if( !extension_loaded('swoole') )
        {
            static::DumpExit('extension swoole does not installed/loaded.');
        }

        if( !extension_loaded('SeasLog') )
        {
            static::DumpExit('extension SeasLog does not installed/loaded.');
        }

        if( (int)str_replace('.', '',(new \ReflectionExtension('swoole'))->getVersion())<400 )
        {
            static::DumpExit('swoole version must be newer than 4.0 .');
        }

        if( (int)str_replace('.', '',(new \ReflectionExtension('SeasLog'))->getVersion())<202 )
        {
            static::DumpExit('seaslog version should be 2.0.2 or newer.');
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

                static::$_function = '_writeToAll';
                break;
            default:
                static::$_function = '_writeToFile';

        }
        static::_selectLogChan();

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
        static::$outputLevel = $config['errorLevel'] ??  static::$outputLevel;
        static::$logTimeZone = $config['timeZone'] ??  static::TIME_ZONE;
        static::$filePath   = static::$baseDir.DIRECTORY_SEPARATOR.static::_getLogFileName().'.log';
        static::$StdoutFile = static::$baseDir.DIRECTORY_SEPARATOR.'ArrowWorker.output';
        error_reporting((int)static::$outputLevel);
        date_default_timezone_set(self::$logTimeZone);
    }

    private static function _initLogSetting()
    {
        if( ini_get('seaslog.default_template')!='%T | %M' )
        {
            static::DumpExit("Value for seaslog.default_template in php.ini should be set to  %T | %M");
        }

        if( ini_get('seaslog.disting_type')!='1' )
        {
            static::DumpExit("Value for seaslog.disting_type in php.ini should be set to 1");
        }

        if( ini_get('seaslog.trace_notice')!='1' )
        {
            static::DumpExit("Value for seaslog.trace_notice in php.ini should be set to 1");
        }

        if( ini_get('seaslog.trace_warning')!= '1' )
        {
            static::DumpExit("Value for seaslog.trace_warning in php.ini should be set to 1");
        }

        if( ini_get('seaslog.trace_exception')!='1' )
        {
            static::DumpExit("Value for seaslog.trace_exception in php.ini should be set to 1");
        }

        if( ini_get('seaslog.trace_error')!='1' )
        {
            static::DumpExit("Value for seaslog.trace_error in php.ini should be set to 1");
        }

        if( ini_get('seaslog.buffer_disabled_in_cli')!= '1' )
        {
            static::DumpExit("Value for seaslog.buffer_disabled_in_cli in php.ini should be set to 1");
        }

        if( ini_get('seaslog.buffer_disabled_in_cli')!= '1' )
        {
            static::DumpExit("Value for seaslog.buffer_disabled_in_cli in php.ini should be set to 1");
        }
        \SeasLog::setBasePath(static::$baseDir);
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
     * @param string $module
     * @return void
     */
    public static function Info(string $log, string $module='')
    {
        static::_selectLogChan()->Write("I|{$module}|{$log}");
    }

    /**
     * Info write an information log
     * @param string $log
     * @param string $module
     * @return void
     */
    public static function Alert(string $log, string $module='')
    {
        static::_selectLogChan()->Write("A|{$module}|{$log}");
    }

    /**
     * @param string $log
     * @param string $module
     * @return void
     */
    public static function Debug(string $log, string $module='')
    {
        static::_selectLogChan()->Write("D|{$module}|{$log}");
    }

    /**
     * Notice : write an notice log
     * @param string $log
     * @param string $module
     * @return void
     */
    public static function Notice(string $log, string $module='')
    {
        static::_selectLogChan()->Write("N|{$module}|{$log}");
    }


    /**
     * Warning : write an warning log
     * @param string $log
     * @param string $module
     * @return void
     */
    public static function Warning(string $log, string $module='')
    {
        static::_selectLogChan()->Write("W|{$module}|{$log}");
    }

    /**
     * Error : write an error log
     * @param string $log
     * @param string $module
     * @return void
     */
    public static function Error(string $log, string $module='')
    {
        static::_selectLogChan()->Write("E|{$module}|{$log}");
    }

    /**
     * Emergency : write an Emergency log
     * @param string $log
     * @param string $module
     * @return void
     */
    public static function Emergency(string $log, string $module='')
    {
        static::_selectLogChan()->Write("EM|{$module}|{$log}");
    }


    /**
     * Critical : write a Critical log
     * @param string $log
     * @param string $module
     * @return void
     */
    public static function Critical(string $log, string $module='')
    {
        static::_selectLogChan()->Write("C|{$module}|{$log}");
    }

    /**
     * Dump : echo log to standard output
     * @param string $log
     */
    public static function Dump(string $log)
    {
        echo sprintf("%s - %s".PHP_EOL,static::_getTime(), $log);
    }

    private static function _getTime()
    {
        return date('Y-m-d H:i:s');
    }

    /**
     * Dump : echo log to standard output
     * @param string $log
     */
    public static function DumpExit(string $log)
    {
        exit(PHP_EOL.$log.PHP_EOL);
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
        $log = static::_selectLogChan()->Read(1);
        if( $log === false )
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
        $log = static::_selectLogChan()->Read(1);
        if( $log === false )
        {
            usleep(100000);
            return ;
        }
        static::_seaslogWrite($log);

    }

    private static function _seaslogWrite(string $log)
    {
        $logInfo  = explode('|', $log);
        $level    = $logInfo[0];
        $module   = $logInfo[1];
        $message  = substr($log, strlen($level.$module)+2);

        $tryTimes = 0;
        RETRY:
        switch ($level)
        {
            case 'A':
                $result = \SeasLog::alert($message, [], $module);
                break;
            case 'D':
                $result = \SeasLog::debug($message, [], $module);
                break;
            case 'E':
                $result = \SeasLog::error($message, [], $module);
                break;
            case 'W':
                $result = \SeasLog::warning($message, [], $module);
                break;
            case 'N':
                $result = \SeasLog::notice($message, [], $module);
                break;
            case 'C':
                $result = \SeasLog::critical($message, [], $module);
                break;
            case 'EM':
                $result = \SeasLog::emergency($message, [], $module);
                break;
            default:
                $result = \SeasLog::info($message, [], $module);
        }

        //写日志失败则重试，重试3次
        if( $result==false )
        {
            $tryTimes++;
            if( $tryTimes<3 )
            {
                goto RETRY;
            }
        }
    }

    /**
     * _writeToAll : write log to both redis queue and file
     */
    private static function _writeToAll()
    {
        $log = static::_selectLogChan()->Read(1);
        if( $log === false )
        {
            usleep(100000);
            return ;
        }


        static::_seaslogWrite($log);

        $tryTimes = 0;
        WriteRedis:
        $tryTimes++;
        if( false === static::$redis->Lpush(static::$queue, $log) && $tryTimes<3 )
        {
            goto WriteRedis;
        }
    }

    /**
     * Start : start a log process
     */
    public static function Start()
    {
        static::_setSignalHandler();
        $function = static::$_function;

        go(function() use ($function) {
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
        });

    }

    /**
     * _exit : exit log process while there are no message in log queue
     */
    private static function _exit()
    {
        var_dump(static::_selectLogChan()->Status());
        exit;
        if( static::_selectLogChan()->Status()['msg_qnum']===0 )
        {
            static::DumpExit('Log queue status : '.json_encode(static::_selectLogChan()->Status()));
        }
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
        var_dump($signal);
        if( $signal==SIGTERM  )
        {
            self::$isTerminate = true;
        }
    }

}