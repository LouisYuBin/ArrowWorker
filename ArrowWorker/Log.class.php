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
use ArrowWorker\Client\Tcp;

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
     *
     */
    const DEFAULT_LOG_DIR = 'default';


    /**
     * period for checkout log file size
     * @var int
     */
    const SIZE_CHECK_PERIOD = 10;

    /**
     * tcp client heartbeat period
     */
    const TCP_HEARTBEAT_PERIOD = 30;

    /**
     *
     */
    const LOG_NAME = __CLASS__;



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
     * @var Queue
     */
    private static $_msgObject;

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
        'file',
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
        'password' => '',
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
    private static $logTimeZone = 'Asia/Shanghai';

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
     * @var string
     */
    private static $_logId = [];

    /**
     * @var array
     */
    private static $_fileHandlerMap = [];


    /**
     * Init log process
     */
    public static function Init()
    {
        self::_checkExtension();
        self::_initConfig();
        self::_checkLogDir();
        self::_initHandler();
        self::_resetStd();
        self::_initMsgInstance();
    }

    /**
     *
     */
    private static function _checkExtension()
    {
        if ( !extension_loaded( 'swoole' ) )
        {
            self::DumpExit( 'extension swoole does not installed/loaded.' );
        }

        if ( (int)str_replace( '.', '', ( new \ReflectionExtension( 'swoole' ) )->getVersion() ) < 400 )
        {
            self::DumpExit( 'swoole version must be newer than 4.0 .' );
        }

    }

    /**
     *
     */
    private static function _checkLogDir()
    {
        if ( !is_dir( self::$_baseDir ) )
        {
            if ( !mkdir( self::$_baseDir ) )
            {
                self::DumpExit( 'creating log directory failed.' );
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

        static::$_tcpConfig = isset( $config[ 'tcp' ] ) && is_array( $config[ 'tcp' ] ) ?
            array_merge( static::$_tcpConfig, $config[ 'tcp' ] ) :
            static::$_tcpConfig;

        static::$_redisConfig = isset( $config[ 'redis' ] ) && is_array( $config[ 'redis' ] ) ?
            array_merge( static::$_redisConfig, $config[ 'redis' ] ) :
            static::$_redisConfig;

        static::$_bufSize    = $config[ 'bufSize' ] ?? static::$_bufSize;
        static::$_chanSize   = $config[ 'chanSize' ] ?? static::$_bufSize;
        static::$_baseDir    = $config[ 'baseDir' ] ?? static::$_baseDir;
        static::$_writeType  = $config[ 'type' ] ?? static::$_writeType;
        static::$outputLevel = $config[ 'errorLevel' ] ?? static::$outputLevel;
        static::$logTimeZone = $config[ 'timeZone' ] ?? static::TIME_ZONE;
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
                        'host'     => static::$_redisConfig[ 'host' ],
                        'port'     => static::$_redisConfig[ 'port' ],
                        'password' => static::$_redisConfig[ 'password' ],
                    ],
                        'log'
                    );
                    break;

                case static::TO_TCP;

                    static::$_toTcpChan = new swChan( static::$_chanSize );
                    static::$_tcpClient = Tcp::Init( static::$_tcpConfig[ 'host' ], static::$_tcpConfig[ 'port' ] );
                    break;

                default:
                    // todo

            }
        }

    }

    /**
     * Info write an information log
     * @param string $log
     * @param string $module
     * @return void
     */
    public static function Info( string $log, string $module = '' )
    {
        self::_fillLog( $log, $module, 'I' );
    }

    /**
     * Info write an information log
     * @param string $log
     * @param string $module
     * @return void
     */
    public static function Alert( string $log, string $module = '' )
    {
        self::_fillLog( $log, $module, 'A' );
    }

    /**
     * @param string $log
     * @param string $module
     * @return void
     */
    public static function Debug( string $log, string $module = '' )
    {
        self::_fillLog( $log, $module, 'D' );
    }

    /**
     * Notice : write an notice log
     * @param string $log
     * @param string $module
     * @return void
     */
    public static function Notice( string $log, string $module = '' )
    {
        self::_fillLog( $log, $module, 'N' );
    }

    /**
     * Warning : write an warning log
     * @param string $log
     * @param string $module
     * @return void
     */
    public static function Warning( string $log, string $module = '' )
    {
        self::_fillLog( $log, $module, 'W' );
    }

    /**
     * Error : write an error log
     * @param string $log
     * @param string $module
     * @return void
     */
    public static function Error( string $log, string $module = '' )
    {
        self::_fillLog( $log, $module, 'E' );
    }

    /**
     * Emergency : write an Emergency log
     * @param string $log
     * @param string $module
     * @return void
     */
    public static function Emergency( string $log, string $module = '' )
    {
        self::_fillLog( $log, $module, 'EM' );
    }

    /**
     * Critical : write a Critical log
     * @param string $log
     * @param string $module
     * @return void
     */
    public static function Critical( string $log, string $module = '' )
    {
        self::_fillLog( $log, $module, 'C' );
    }

    /**
     * @param string $log
     * @param string $module
     * @param string $level
     */
    private static function _fillLog( string $log, string $module = '', string $level = 'D' )
    {
        $time  = date( 'Y-m-d H:i:s' );
        $logId = self::GetLogId();
        self::$_msgObject->Write( "{$level}|{$module}|{$time} | {$logId} | $log" . PHP_EOL );
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
        echo( PHP_EOL . static::_getTime() . ' ' . $log . PHP_EOL );
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
     * @return void
     */
    private static function _initMsgInstance()
    {
        if ( !is_object( self::$_msgObject ) )
        {
            self::$_msgObject = Chan::Get(
                'log',
                [
                    'msgSize' => static::$_msgSize,
                    'bufSize' => static::$_bufSize,
                ]
            );
        }
    }

    /**
     * @param string $log
     */
    private static function _writeLogFile( string $log )
    {
        $logInfo = explode( '|', $log );
        $level   = $logInfo[ 0 ];
        $module  = ''==$logInfo[ 1 ] ? self::DEFAULT_LOG_DIR : $logInfo[ 1 ];
        $message = substr( $log, strlen( $level . $logInfo[ 1 ] ) + 2 );

        $tryTimes = 0;
        RETRY:
        //try three times if failed
        if ( false === self::_writeFile( $module, $level, $message ) )
        {
            $tryTimes++;
            if ( $tryTimes < 3 )
            {
                goto RETRY;
            }
        }
    }

    /**
     * @param string $module
     * @param string $level
     * @param string $message
     * @return bool|int
     */
    private static function _writeFile( string $module, string $level, string $message )
    {
        $date  = date( 'Ymd' );
        $alias = $module . $level . $date;
        if ( isset( self::$_fileHandlerMap[ $alias ] ) )
        {
            $result = fwrite( self::$_fileHandlerMap[ $alias ], $message );
            if ( false === $result )
            {
                goto _INIT;
            }
            return $result;
        }

        _INIT:
        $logDir = self::$_baseDir . $module . '/';
        $logExt = $date.'.'.self::_getFileExt( $level );
        $logRes = self::_initFileHandle( $logDir, $logExt );
        if( false===$logRes )
        {
            return false;
        }
        self::$_fileHandlerMap[ $alias ] = $logRes;
        return fwrite( $logRes, $message );
    }

    /**
     * @param string $fileDir
     * @param string $fileExt
     * @return bool|resource
     */
    private static function _initFileHandle( string $fileDir, string $fileExt )
    {
        $filePath = $fileDir . $fileExt;
        if ( !is_dir( $fileDir ) )
        {
            if ( !mkdir( $fileDir, 0760, true ) )
            {
                Log::Dump( " [ EMERGENCY ] make log directory:{$fileDir} failed . " );
                return false;
            }
        }

        $fileRes = fopen( $filePath, 'a' );
        if ( false === $fileRes )
        {
            Log::Dump( " [ EMERGENCY ] fopen log file:{$filePath} failed . " );
            return false;
        }
        return $fileRes;
    }


    /**
     * @param string $level
     * @return string
     */
    private static function _getFileExt( string $level )
    {
        $ext = '.log';
        switch ( $level )
        {
            case 'A':
                return "Alert{$ext}";
                break;
            case 'D':
                return "Debug{$ext}";
                break;
            case 'E':
                return "Error{$ext}";
                break;
            case 'W':
                return "Warning{$ext}";
                break;
            case 'N':
                return "Notice{$ext}";
                break;
            case 'C':
                return "Critical{$ext}";
                break;
            case 'EM':
                return "Emergency{$ext}";
                break;
            default:
                return "Info{$ext}";
        }
    }

    /**
     * Start : start log process
     */
    public static function Start()
    {
        static::_setSignalHandler();

        $logClass = __CLASS__;

        Co::create( "{$logClass}::WriteToFile" );
        if ( in_array( static::TO_TCP, static::$_writeType ) )
        {
            Co::create( "{$logClass}::WriteToTcp" );
        }

        if ( in_array( static::TO_REDIS, static::$_writeType ) )
        {
            Co::create( "{$logClass}::WriteToRedis" );
        }

        Co::create( "{$logClass}::Dispatch" );

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
                self::$_msgObject->Status()[ 'msg_qnum' ] == 0
            )
            {
                break;
            }

            pcntl_signal_dispatch();

            $log = self::$_msgObject->Read( 10000 );
            if ( $log === false )
            {
                continue;
            }


            static::$_toFileChan->push( $log, 1 );

            if ( in_array( static::TO_TCP, static::$_writeType ) )
            {
                static::$_toTcpChan->push( $log, 1 );
            }

            if ( in_array( static::TO_REDIS, static::$_writeType ) )
            {
                static::$_toRedisChan->push( $log, 1 );
            }

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
            $data = static::$_toFileChan->pop( 0.5 );
            if ( static::$isTerminateChan && $data === false )
            {
                break;
            }

            if ( $data === false )
            {
                Co::sleep( 1 );
                continue;
            }

            static::_writeLogFile( $data );

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
            $data = static::$_toTcpChan->pop( 0.5 );
            if ( static::$isTerminateChan && $data === false )
            {
                break;
            }

            if ( $data === false )
            {
                Co::sleep( 1 );
                continue;
            }

            if ( false == static::$_tcpClient->Send( $data, 3 ) )
            {
                Log::Dump( "write tcp client failed. data : {$data}" );
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
            $data = static::$_toRedisChan->pop( 0.5 );
            if ( static::$isTerminateChan && $data === false )
            {
                break;
            }

            if ( $data === false )
            {
                Co::sleep( 1 );
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
        static::DumpExit( 'Log queue status : ' . json_encode( self::$_msgObject->Status() ) );
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
            "signalHandler",
        ], false );
        pcntl_signal( SIGTERM, [
            __CLASS__,
            "signalHandler",
        ], false );

        pcntl_signal( SIGCHLD, SIG_IGN, false );
        pcntl_signal( SIGQUIT, SIG_IGN, false );

        pcntl_alarm( self::TCP_HEARTBEAT_PERIOD );
    }


    /**
     * signalHandler : function for handle signal
     * @param int $signal
     * @author Louis
     */
    public static function signalHandler( int $signal )
    {
        switch ( $signal )
        {
            case SIGALRM:
                self::_handleAlarm();
                break;
            case SIGTERM:
                self::$isTerminate = true;
                break;
            default:
        }
    }

    /**
     * handle log process alarm signal
     */
    private static function _handleAlarm()
    {
        self::_sendTcpHeartbeat();
        self::_cleanUselessFileHandler();
        pcntl_alarm( self::TCP_HEARTBEAT_PERIOD );
    }

    /**
     *
     */
    private static function _cleanUselessFileHandler()
    {
        $time = (int)date('Hi');
        if( $time>2 )
        {
            return ;
        }

        self::SetLogId();
        $today = date('Ymd');
        foreach (self::$_fileHandlerMap as $alias=>$handler)
        {
            $aliasDate = substr($alias,strlen($alias)-8,8);
            if( $today!=$aliasDate )
            {
                fclose(self::$_fileHandlerMap[$alias]);
                unset(self::$_fileHandlerMap[$alias]);
                Log::Debug("log file handler : {$alias} was cleaned.", self::LOG_NAME);
            }
        }
    }

    /**
     *
     */
    private static function _sendTcpHeartbeat()
    {
        if ( is_object( self::$_tcpClient ) )
        {
            self::$_tcpClient->Send( 'heartbeat' );
        }
    }

    /**
     * @param string $logId
     */
    public static function SetLogId( string $logId = '' )
    {
        self::$_logId[ Swoole::GetCid() ] = '' === $logId ? date( 'ymdHis' ) .
                                                            posix_getpid() .
                                                            Swoole::GetCid() .
                                                            mt_rand( 100, 999 ) : $logId;
    }

    /**
     * @return string
     */
    public static function GetLogId() : string
    {
        return self::$_logId[ Swoole::GetCid() ];
    }

    /**
     *
     */
    public static function Release()
    {
        unset( self::$_logId[ Swoole::GetCid() ] );
    }

}