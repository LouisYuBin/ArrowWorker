<?php
/**
 * User: louis
 * Time: 18-6-10 上午1:16
 */

namespace ArrowWorker;


use ArrowWorker\Driver\Cache\Redis;
use ArrowWorker\Driver\Channel\Queue;
use \Swoole\Coroutine\Channel as swChan;
use \Swoole\Event as swEvent;
use \Swoole\Coroutine as Co;
use ArrowWorker\Lib\Client\Tcp;

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
    const TO_FILE = 'file';

    /**
     * write log to redis queue
     * @var string
     */
    const TO_REDIS = 'redis';

    /**
     * write log to tcp server
     * @var string
     */
    const TO_TCP = 'tcp';

    /**
     * write log to file 、 redis and tcp server
     * @var string
     */
    const TO_ALL = 'all';


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
     * bufSize : log buffer size 10M
     * @var int
     */
    private static $_bufSize = 10485760;

    /**
     * chanSize : log buffer size 10M
     * @var int
     */
    private static $_chanSize = 10485760;

    /**
     * msgSize : a single log size 1M
     * @var int
     */
    private static $_msgSize = 1048576;

    /**
     * directory for store log files
     * @var string
     */
    private static $_baseDir = APP_PATH . DIRECTORY_SEPARATOR . APP_RUNTIME_DIR . '/Log/';

    /**
     * write log to file
     * @var array
     */
    private static $_writeType = [
        'file'
    ];

    /**
     * password of redis
     * @var array
     */
    private static $_tcpConfig = [
        'host' => '127.0.0.1',
        'port' => '6379',
    ];

    /**
     * @var array
     */
    private static $_redisConfig = [
        'host'     => '127.0.0.1',
        'port'     => '6379',
        'queue'    => 'ArrowLog',
        'password' => ''
    ];

    /**
     * queue of redis for log
     * @var string
     */
    private static $queue = 'ArrowLog';

    /**
     * Whether to close the log process
     * @var bool
     */
    private static $isTerminate = false;

    /**
     * Whether to close the log channel
     * @var bool
     */
    private static $isTerminateChan = false;


    /**
     * @var string
     */
    private static $logTimeZone = 'UTC';

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
    private static $_redisClient;


    /**
     *
     * @var Tcp
     */
    private static $_tcpClient;


    //日志等级，1:E_ERROR , 2:E_WARNING , 8:E_NOTICE , 2048:E_STRICT , 30719:all
    /**
     * @var int
     */
    private static $outputLevel = 30719;

    /**
     * @var \Swoole\Coroutine\Channel;
     */
    private static $_toFileChan;

    /**
     * @var \Swoole\Coroutine\Channel
     */
    private static $_toTcpChan;

    /**
     * @var \Swoole\Coroutine\Channel
     */
    private static $_toRedisChan;


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

    /**
     *
     */
    private static function _checkExtension()
    {
        if ( !extension_loaded( 'swoole' ) )
        {
            static::DumpExit( 'extension swoole does not installed/loaded.' );
        }

        if ( !extension_loaded( 'SeasLog' ) )
        {
            static::DumpExit( 'extension SeasLog does not installed/loaded.' );
        }

        if ( (int)str_replace( '.', '', (new \ReflectionExtension( 'swoole' ))->getVersion() ) < 400 )
        {
            static::DumpExit( 'swoole version must be newer than 4.0 .' );
        }

        if ( (int)str_replace( '.', '', (new \ReflectionExtension( 'SeasLog' ))->getVersion() ) < 202 )
        {
            static::DumpExit( 'seaslog version should be 2.0.2 or newer.' );
        }

    }

    /**
     *
     */
    private static function _checkLogDir()
    {
        if ( !is_dir( static::$_baseDir ) )
        {
            if ( !mkdir( static::$_baseDir ) )
            {
                static::DumpExit( 'creating log directory failed.' );
            }
        }
    }

    /**
     * _initConfig : init log configuration
     */
    private static function _initConfig()
    {
        $config = Config::Get( 'Log' );
        if ( false === $config )
        {
            return;
        }

        static::$_tcpConfig = isset( $config['tcp'] ) && is_array( $config['tcp'] ) ?
            array_merge( static::$_tcpConfig, $config['tcp'] ) :
            static::$_tcpConfig;

        static::$_redisConfig = isset( $config['redis'] ) && is_array( $config['redis'] ) ?
            array_merge( static::$_redisConfig, $config['redis'] ) :
            static::$_redisConfig;

        static::$_bufSize    = $config['bufSize'] ?? static::$_bufSize;
        static::$_chanSize   = $config['chanSize'] ?? static::$_bufSize;
        static::$_baseDir    = $config['baseDir'] ?? static::$_baseDir;
        static::$_writeType  = $config['type'] ?? static::$_writeType;
        static::$outputLevel = $config['errorLevel'] ?? static::$outputLevel;
        static::$logTimeZone = $config['timeZone'] ?? static::TIME_ZONE;
        static::$StdoutFile  = static::$_baseDir . DIRECTORY_SEPARATOR . 'ArrowWorker.output';
        error_reporting( (int)static::$outputLevel );
        date_default_timezone_set( self::$logTimeZone );
    }

    /**
     * _initHandler : init redis and file for log
     */
    private static function _initHandler()
    {
        static::$_toFileChan = new swChan( static::$_chanSize );

        foreach ( static::$_writeType as $type )
        {
            switch ( $type )
            {
                case static::TO_REDIS:

                    static::$_toRedisChan = new swChan( static::$_chanSize );
                    static::$_redisClient = Redis::Init( [
                                                             'host'     => static::$_redisConfig['host'],
                                                             'port'     => static::$_redisConfig['port'],
                                                             'password' => static::$_redisConfig['password']
                                                         ],
                                                         'log'
                    );
                    break;

                case static::TO_TCP;

                    static::$_toTcpChan = new swChan( static::$_chanSize );
                    static::$_tcpClient = Tcp::Init( static::$_tcpConfig['host'], static::$_tcpConfig['port'] );
                    break;

                default:
                    // todo

            }
        }

        static::_selectLogChan();
    }

    /**
     *
     */
    private static function _initLogSetting()
    {

        if ( ini_get( 'seaslog.buffer_disabled_in_cli' ) != '1' )
        {
            static::DumpExit( "Value for seaslog.buffer_disabled_in_cli in php.ini should be set to 1" );
        }

        if ( ini_get( 'seaslog.buffer_disabled_in_cli' ) != '1' )
        {
            static::DumpExit( "Value for seaslog.buffer_disabled_in_cli in php.ini should be set to 1" );
        }

        \SeasLog::setBasePath( static::$_baseDir );
    }


    /**
     * Info write an information log
     * @param string $log
     * @param string $module
     * @return void
     */
    public static function Info( string $log, string $module = '' )
    {
        static::_selectLogChan()
              ->Write( "I|{$module}|{$log}" );
    }

    /**
     * Info write an information log
     * @param string $log
     * @param string $module
     * @return void
     */
    public static function Alert( string $log, string $module = '' )
    {
        static::_selectLogChan()
              ->Write( "A|{$module}|{$log}" );
    }

    /**
     * @param string $log
     * @param string $module
     * @return void
     */
    public static function Debug( string $log, string $module = '' )
    {
        static::_selectLogChan()
              ->Write( "D|{$module}|{$log}" );
    }

    /**
     * Notice : write an notice log
     * @param string $log
     * @param string $module
     * @return void
     */
    public static function Notice( string $log, string $module = '' )
    {
        static::_selectLogChan()
              ->Write( "N|{$module}|{$log}" );
    }


    /**
     * Warning : write an warning log
     * @param string $log
     * @param string $module
     * @return void
     */
    public static function Warning( string $log, string $module = '' )
    {
        static::_selectLogChan()
              ->Write( "W|{$module}|{$log}" );
    }

    /**
     * Error : write an error log
     * @param string $log
     * @param string $module
     * @return void
     */
    public static function Error( string $log, string $module = '' )
    {
        static::_selectLogChan()
              ->Write( "E|{$module}|{$log}" );
    }

    /**
     * Emergency : write an Emergency log
     * @param string $log
     * @param string $module
     * @return void
     */
    public static function Emergency( string $log, string $module = '' )
    {
        static::_selectLogChan()
              ->Write( "EM|{$module}|{$log}" );
    }


    /**
     * Critical : write a Critical log
     * @param string $log
     * @param string $module
     * @return void
     */
    public static function Critical( string $log, string $module = '' )
    {
        static::_selectLogChan()
              ->Write( "C|{$module}|{$log}" );
    }

    /**
     * Dump : echo log to standard output
     * @param string $log
     */
    public static function Dump( string $log )
    {
        echo sprintf( "%s - %s" . PHP_EOL, static::_getTime(), $log );
    }

    /**
     * @return false|string
     */
    private static function _getTime()
    {
        return date( 'Y-m-d H:i:s' );
    }

    /**
     * Dump : echo log to standard output
     * @param string $log
     */
    public static function DumpExit( string $log )
    {
        echo(PHP_EOL . $log . PHP_EOL);
        exit( 0 );
    }

    /**
     * @param string $log
     */
    public static function Hint( string $log )
    {
        echo $log . PHP_EOL;
    }


    /**
     * _selectLogChan : select the log chan
     * @return Queue
     */
    private static function _selectLogChan() : Queue
    {
        return Queue::Init(
            [
                'msgSize' => static::$_msgSize,
                'bufSize' => static::$_bufSize
            ],
            'log'
        );
    }

    /**
     * @param string $log
     */
    private static function _seaslogWrite( string $log )
    {
        $logInfo = explode( '|', $log );
        $level   = $logInfo[0];
        $module  = $logInfo[1];
        $message = substr( $log, strlen( $level . $module ) + 2 );
        $tryTimes = 0;
        RETRY:
        switch ( $level )
        {
            case 'A':
                $result = \SeasLog::alert( $message, [], $module );
                break;
            case 'D':
                $result = \SeasLog::debug( $message, [], $module );
                break;
            case 'E':
                $result = \SeasLog::error( $message, [], $module );
                break;
            case 'W':
                $result = \SeasLog::warning( $message, [], $module );
                break;
            case 'N':
                $result = \SeasLog::notice( $message, [], $module );
                break;
            case 'C':
                $result = \SeasLog::critical( $message, [], $module );
                break;
            case 'EM':
                $result = \SeasLog::emergency( $message, [], $module );
                break;
            default:
                $result = \SeasLog::info( $message, [], $module );
        }

        //写日志失败则重试，重试3次
        if ( $result == false )
        {
            $tryTimes++;
            if ( $tryTimes < 3 )
            {
                goto RETRY;
            }
        }
    }

    /**
     * Start : start log process
     */
    public static function Start()
    {
        static::_setSignalHandler();

        $logClass = __CLASS__;
        Co::create( "{$logClass}::Dispatch" );
        Co::create( "{$logClass}::WriteToFile" );
        if ( in_array( static::TO_TCP, static::$_writeType ) )
        {
            Co::create( "{$logClass}::WriteToTcp" );
        }

        if ( in_array( static::TO_REDIS, static::$_writeType ) )
        {
            Co::create( "{$logClass}::WriteToRedis" );
        }

        swEvent::wait();
        static::_exit();
    }

    /**
     *
     */
    public static function Dispatch()
    {
        while ( true )
        {
            if (
                static::$isTerminate &&
                static::_selectLogChan()->Status()['msg_qnum'] == 0
            )
            {
                break;
            }

            pcntl_signal_dispatch();

            $log = static::_selectLogChan()->Read( 1 );
            if ( $log === false )
            {
                continue;
            }

            static::$_toFileChan->push( $log, 1 );
            static::$_toTcpChan->push( $log, 1 );

            pcntl_signal_dispatch();

        }

        static::$isTerminateChan = true;
        self::Dump( 'log dispatch coroutine exited' );
    }

    /**
     *
     */
    public static function WriteToFile()
    {
        while ( true )
        {
            $data = static::$_toFileChan->pop( 0.3 );
            if ( static::$isTerminateChan && $data === false )
            {
                break;
            }

            if ( $data === false )
            {
                Co::sleep(1);
                continue;
            }

            static::_seaslogWrite( $data );

        }
        self::Dump( 'log file-writing coroutine exited' );
    }

    /**
     *
     */
    public static function WriteToTcp()
    {
        while ( true )
        {
            $data = static::$_toTcpChan->pop( 0.3 );
            if ( static::$isTerminateChan && $data === false )
            {
                break;
            }

            if ( $data === false )
            {
                continue;
            }

            if( false==static::$_tcpClient->Send( $data, 3) )
            {
                Log::Dump("write tcp client failed. data : {$data}");
            }
        }
        self::Dump( 'log tcp-writing coroutine exited' );
    }

    /**
     * public : write log to redis queue
     */
    public static function WriteToRedis()
    {
        while ( true )
        {
            $data = static::$_toRedisChan->pop(0.3 );
            if ( static::$isTerminateChan && $data === false )
            {
                break;
            }

            if ( $data === false )
            {
                continue;
            }

            for ( $i = 0; $i < 3; $i++ )
            {
                if ( false !== static::$_redisClient->Lpush( static::$queue, $data ) )
                {
                    break;
                }
            }

        }

    }


    /**
     * _exit : exit log process while there are no message in log queue
     */
    private static function _exit()
    {
        static::DumpExit( 'Log queue status : ' . json_encode( static::_selectLogChan()
                                                                     ->Status() ) );
    }

    /**
     * _resetStd reset standard output and error log
     * @author Louis
     */
    private static function _resetStd()
    {
        global $STDOUT, $STDERR;
        static::$_stdout = fopen( static::$StdoutFile, "a" );
        if ( is_resource( static::$_stdout ) )
        {
            fclose( STDOUT );
            fclose( STDERR );
            $STDOUT = fopen( static::$StdoutFile, 'a' );
            $STDERR = fopen( static::$StdoutFile, 'a' );
            return;

        }
        else
        {
            die( "ArrowWorker hint : can not open stdoutFile" . PHP_EOL );
        }
    }

    /**
     * _setSignalHandler : set function for signal handler
     * @author Louis
     */
    private static function _setSignalHandler()
    {
        pcntl_signal( SIGALRM, [
            __CLASS__,
            "signalHandler"
        ], false );
        pcntl_signal( SIGTERM, [
            __CLASS__,
            "signalHandler"
        ], false );

        pcntl_signal( SIGCHLD, SIG_IGN, false );
        pcntl_signal( SIGQUIT, SIG_IGN, false );
    }


    /**
     * signalHandler : function for handle signal
     * @author Louis
     * @param int $signal
     */
    public static function signalHandler( int $signal )
    {
        if ( $signal == SIGTERM )
        {
            self::$isTerminate = true;
        }
    }

}