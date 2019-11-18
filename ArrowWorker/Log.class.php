<?php
/**
 * User: louis
 * Time: 18-6-10 上午1:16
 */

namespace ArrowWorker;


use \Swoole\Coroutine\Channel as swChan;

use ArrowWorker\Driver\Cache\Redis;
use ArrowWorker\Driver\Channel\Queue;
use ArrowWorker\Client\Tcp\Client as Tcp;
use ArrowWorker\Lib\Coroutine;
use ArrowWorker\Lib\Process;

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


    const MAX_BUFFER_SIZE = 512;

    /**
     *
     */
    const DEFAULT_LOG_DIR = 'default';

    /**
     * tcp client heartbeat period
     */
    const TCP_HEARTBEAT_PERIOD = 30;

    /**
     *
     */
    const LOG_NAME = 'Log';

    /**
     *
     */
    const LOG_PREFIX = '[   Log   ] ';


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
     * @var
     */
    public static $StdoutFile;

    /**
     * @var Queue
     */
    private static $_msgObject;

    /**
     * @var array
     */
    private static $_logId = [];

    /**
     * Whether to close the log process
     * @var bool
     */
    private $_isTerminate = false;

    /**
     * Whether to close the log channel
     * @var bool
     */
    private $_isTerminateChan = false;

    /**
     *
     * @var Tcp
     */
    private $_tcpClient;

    /**
     * redis instance
     * @var Redis
     */
    private $_redisClient;

    /**
     * @var swChan;
     */
    private $_toFileChan;

    /**
     * @var swChan
     */
    private $_toTcpChan;

    /**
     * @var swChan
     */
    private $_toRedisChan;

    /**
     * @var array
     */
    private $_fileHandlerMap = [];

    private $_buffer = [];

    private $_bufTime = [];


    /**
     * Initialize log process
     */
    public static function Initialize()
    {
        self::_checkExtension();
        self::_initConfig();
        self::_checkLogDir();
        self::_initMsgObj();
        self::_resetStd();
    }

    private function __construct()
    {
        $this->_initHandler();
        $this->_initSignalHandler();
    }

    private static function _checkExtension()
    {
        if ( !extension_loaded( 'swoole' ) )
        {
            self::DumpExit( 'extension swoole is not installed/loaded.' );
        }

        if ( !extension_loaded( 'sysvmsg' ) )
        {
            self::DumpExit( 'extension sysvmsg is not installed/loaded.' );
        }

        if ( (int)str_replace( '.', '', ( new \ReflectionExtension( 'swoole' ) )->getVersion() ) < 400 )
        {
            self::DumpExit( 'swoole version must be newer than 4.0 .' );
        }

    }

    private static function _checkLogDir()
    {
        if ( !is_dir( self::$_baseDir ) )
        {
            if ( !mkdir( self::$_baseDir, 0777, true ) )
            {
                self::DumpExit( 'creating log directory failed.' );
            }
        }
    }

    private static function _initConfig()
    {
        $config = Config::Get( 'Log' );
        if ( false === $config )
        {
            return;
        }

        self::$_tcpConfig = isset( $config[ 'tcp' ] ) && is_array( $config[ 'tcp' ] ) ?
            array_merge( self::$_tcpConfig, $config[ 'tcp' ] ) :
            self::$_tcpConfig;

        self::$_redisConfig = isset( $config[ 'redis' ] ) && is_array( $config[ 'redis' ] ) ?
            array_merge( self::$_redisConfig, $config[ 'redis' ] ) :
            self::$_redisConfig;

        self::$_bufSize   = $config[ 'bufSize' ] ?? self::$_bufSize;
        self::$_chanSize  = $config[ 'chanSize' ] ?? self::$_bufSize;
        self::$_baseDir   = $config[ 'baseDir' ] ?? self::$_baseDir;
        self::$_writeType = $config[ 'type' ] ?? self::$_writeType;
        self::$StdoutFile = self::$_baseDir . DIRECTORY_SEPARATOR . 'Arrow.log';
    }


    private function _initHandler()
    {
        $this->_toFileChan = new swChan( self::$_chanSize );

        foreach ( self::$_writeType as $type )
        {
            switch ( $type )
            {
                case self::TO_REDIS:

                    $this->_toRedisChan = new swChan( self::$_chanSize );
                    $this->_redisClient = Redis::Init( [
                        'host'     => self::$_redisConfig[ 'host' ],
                        'port'     => self::$_redisConfig[ 'port' ],
                        'password' => self::$_redisConfig[ 'password' ],
                    ],
                        'log'
                    );
                    break;

                case self::TO_TCP;

                    $this->_toTcpChan = new swChan( self::$_chanSize );
                    $this->_tcpClient = Tcp::Init( self::$_tcpConfig[ 'host' ], self::$_tcpConfig[ 'port' ] );
                    break;

                default:
                    // nothing need to be done

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
        self::Dump( '[' . str_pad( $module, 9, ' ', STR_PAD_BOTH ) . '] ' . $log );
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
        self::$_msgObject->Write( "{$level}�{$module}�{$time} | {$logId} | $log" . PHP_EOL );
    }

    /**
     * Dump : echo log to standard output
     * @param string $log
     */
    public static function Dump( string $log )
    {
        echo sprintf( "%s %s" . PHP_EOL, static::_getTime(), $log );
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
    private static function _initMsgObj()
    {
        if ( !is_object( self::$_msgObject ) )
        {
            self::$_msgObject = Chan::Get(
                'log',
                [
                    'msgSize' => self::$_msgSize,
                    'bufSize' => self::$_bufSize,
                ]
            );
        }
    }

    /**
     * @param string $log
     */
    private function _writeLogFile( string $log )
    {
        $logInfo = explode( '�', $log );
        $level   = $logInfo[ 0 ];
        $module  = '' == $logInfo[ 1 ] ? self::DEFAULT_LOG_DIR : $logInfo[ 1 ];
        $message = substr( $log, strlen( $level . $logInfo[ 1 ] ) + 6 );
        $this->_writeFile( $module, $level, $message );
    }

    /**
     * @param string $module
     * @param string $level
     * @param string $log
     * @return void
     */
    private function _writeFile( string $module, string $level, string $log )
    {
        $date  = date( 'Ymd' );
        $alias = $module . $level . $date;

        if( !isset($this->_buffer[$alias]) )
        {
            $this->_buffer[ $alias ] = $log;
            $this->_bufTime[$alias]  = time();
        }
        else
        {
            $this->_buffer[ $alias ] = "{$this->_buffer[$alias]}{$log}";
        }

        if( time()-$this->_bufTime[$alias] >=2 )
        {
            goto CHECK_FILE_HANDLER;
        }

        if ( strlen( $this->_buffer[ $alias ] ) < self::MAX_BUFFER_SIZE )
        {
            return;
        }

        CHECK_FILE_HANDLER:
        if ( isset( $this->_fileHandlerMap[ $alias ] ) )
        {
            goto WRITE_LOG;
        }

        $fileDir = self::$_baseDir . $module . '/';
        $fileExt = $date . '.' . $this->_getFileExt( $level );
        $logRes = $this->_initFileHandle( $fileDir, $fileExt );
        if ( false === $logRes )
        {
            Log::Dump( self::LOG_PREFIX . " [ Emergency ] _initFileHandle failed, file directory : {$fileDir}, file ext : {$fileExt}, log : {$log}" );
            $this->_buffer[ $alias ] = '';
            return ;
        }
        $this->_fileHandlerMap[ $alias ] = $logRes;

        WRITE_LOG:
        $result = Coroutine::FileWrite( $this->_fileHandlerMap[ $alias ], $this->_buffer[ $alias ] );
        if ( false === $result )
        {
            Log::Dump( self::LOG_PREFIX . " [ Emergency ] Coroutine::FileWrite failed, log : {$log}" );
        }
        $this->_bufTime[ $alias ] = time();
        $this->_buffer[ $alias ]  = '';
    }

    /**
     * @param string $fileDir
     * @param string $fileExt
     * @return bool|resource
     */
    private function _initFileHandle( string $fileDir, string $fileExt )
    {
        $filePath = $fileDir . $fileExt;
        if ( !is_dir( $fileDir ) )
        {
            if ( !mkdir( $fileDir, 0666, true ) )
            {
                Log::Dump( self::LOG_PREFIX . " [ EMERGENCY ] make log directory:{$fileDir} failed . " );
                return false;
            }
        }

        $fileRes = fopen( $filePath, 'a' );
        if ( false === $fileRes )
        {
            Log::Dump( self::LOG_PREFIX . " [ EMERGENCY ] fopen log file:{$filePath} failed . " );
            return false;
        }
        return $fileRes;
    }


    /**
     * @param string $level
     * @return string
     */
    private function _getFileExt( string $level )
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
        $log = new self();
        $log->_initCoroutine();
        $log->_exit();
    }

    private function _initCoroutine()
    {
        Coroutine::Enable();
        for ( $i = 0; $i < 72; $i++ )
        {
            Coroutine::Create( function ()
            {
                $this->WriteToFile();
            } );
        }

        if ( in_array( self::TO_TCP, self::$_writeType ) )
        {
            Coroutine::Create( function ()
            {
                $this->WriteToTcp();
            } );
        }

        if ( in_array( self::TO_REDIS, self::$_writeType ) )
        {
            Coroutine::Create( function ()
            {
                $this->WriteToRedis();
            } );
        }

        for ( $i = 0; $i < 2; $i++ )
        {
            Coroutine::Create( function ()
            {
                $this->Dispatch();
            } );
        }
        Coroutine::Wait();
    }

    public function Dispatch()
    {
        $msgQueue = self::$_msgObject;
        while ( true )
        {
            if (
                $this->_isTerminate &&
                $msgQueue->Status()[ 'msg_qnum' ] == 0
            )
            {
                break;
            }

            pcntl_signal_dispatch();

            $log = $msgQueue->Read( 10000 );
            if ( $log === false )
            {
                continue;
            }

            if ( false == $this->_toFileChan->push( $log, 1 ) )
            {
                Log::Dump( "Push log chan failed, data:{$log}, error code： " . $this->_toFileChan->errCode . "}" );
            }

            if ( in_array( static::TO_TCP, static::$_writeType ) )
            {
                $this->_toTcpChan->push( $log, 1 );
            }

            if ( in_array( static::TO_REDIS, static::$_writeType ) )
            {
                $this->_toRedisChan->push( $log, 1 );
            }

            pcntl_signal_dispatch();

        }

        $this->_isTerminateChan = true;
        //self::Dump( self::LOG_PREFIX.'dispatch coroutine exited' );
    }

    /**
     *
     */
    public function WriteToFile()
    {

        while ( true )
        {
            $data = $this->_toFileChan->pop( 0.2 );
            if ( $this->_isTerminateChan && $data === false )
            {
                break;
            }

            if ( $data === false )
            {
                Coroutine::Sleep( 0.5 );
                continue;
            }

            $this->_writeLogFile( $data );

        }
        //self::Dump( self::LOG_PREFIX.'file-writing coroutine exited' );
    }

    /**
     *
     */
    public function WriteToTcp()
    {
        while ( true )
        {
            $data = $this->_toTcpChan->pop( 0.5 );
            if ( $this->_isTerminateChan && $data === false )
            {
                break;
            }

            if ( $data === false )
            {
                Coroutine::Sleep( 1 );
                continue;
            }

            if ( false == $this->_tcpClient->Send( $data, 3 ) )
            {
                Log::Dump( self::LOG_PREFIX . "write tcp client failed. data : {$data}" );
            }
        }
        self::Dump( self::LOG_PREFIX . 'tcp-writing coroutine exited' );
    }

    /**
     * public : write log to redis queue
     */
    public function WriteToRedis()
    {
        while ( true )
        {
            $data = $this->_toRedisChan->pop( 0.5 );
            if ( $this->_isTerminateChan && $data === false )
            {
                break;
            }

            if ( $data === false )
            {
                Coroutine::Sleep( 1 );
                continue;
            }

            for ( $i = 0; $i < 3; $i++ )
            {
                if ( false !== $this->_redisClient->Lpush( self::$queue, $data ) )
                {
                    break;
                }
            }

        }

    }

    /**
     * _exit : exit log process while there are no message in log queue
     */
    private function _exit()
    {
        static::Dump( self::LOG_PREFIX . ' exited. queue status : ' . json_encode( $this->_msgObject->Status() ) );
        exit( 0 );
    }

    /**
     * _resetStd reset standard output and error log
     * @author Louis
     */
    private static function _resetStd()
    {
        global $STDOUT, $STDERR;
        $newStdResource = fopen( static::$StdoutFile, "a" );
        if ( !is_resource( $newStdResource ) )
        {
            die( "ArrowWorker hint : can not open stdoutFile" . PHP_EOL );
        }

        fclose( STDOUT );
        fclose( STDERR );
        $STDOUT = fopen( static::$StdoutFile, 'a' );
        $STDERR = fopen( static::$StdoutFile, 'a' );
        return;
    }

    /**
     * _setSignalHandler : set function for signal handler
     * @author Louis
     */
    private function _initSignalHandler()
    {
        pcntl_signal( SIGALRM, [
            $this,
            "SignalHandler",
        ], false );
        pcntl_signal( SIGTERM, [
            $this,
            "SignalHandler",
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
    public function SignalHandler( int $signal )
    {
        switch ( $signal )
        {
            case SIGALRM:
                $this->_handleAlarm();
                break;
            case SIGTERM:
                $this->_isTerminate = true;
                break;
            default:
        }
    }

    /**
     * handle log process alarm signal
     */
    private function _handleAlarm()
    {
        self::_sendTcpHeartbeat();
        self::_cleanUselessFileHandler();
        pcntl_alarm( self::TCP_HEARTBEAT_PERIOD );
    }

    /**
     *
     */
    private function _cleanUselessFileHandler()
    {
        $time = (int)date( 'Hi' );
        if ( $time > 2 )
        {
            return;
        }

        self::Init();
        $today = date( 'Ymd' );
        foreach ( $this->_fileHandlerMap as $alias => $handler )
        {
            $aliasDate = substr( $alias, strlen( $alias ) - 8, 8 );
            if ( $today != $aliasDate )
            {
                fclose( $this->_fileHandlerMap[ $alias ] );
                unset( $this->_fileHandlerMap[ $alias ] );
                Log::Debug( "log file handler : {$alias} was cleaned.", self::LOG_NAME );
            }
        }
    }

    /**
     *
     */
    private function _sendTcpHeartbeat()
    {
        if ( is_object( $this->_tcpClient ) )
        {
            $this->_tcpClient->Send( 'heartbeat' );
        }
    }

    /**
     * @param string $logId
     */
    public static function Init( string $logId = '' )
    {
        $coId                  = Coroutine::Id();
        self::$_logId[ $coId ] = '' === $logId ? date( 'ymdHis' ) .
                                                 Process::Id() .
                                                 $coId .
                                                 mt_rand( 100, 999 ) : $logId;
    }

    /**
     * @return string
     */
    public static function GetLogId() : string
    {
        return self::$_logId[ Coroutine::Id() ];
    }

    /**
     *
     */
    public static function Release()
    {
        unset( self::$_logId[ Coroutine::Id() ] );
    }

}