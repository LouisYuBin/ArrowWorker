<?php
/**
 * User: louis
 * Time: 18-6-10 上午1:16
 */

namespace ArrowWorker;


use ArrowWorker\Component\Cache\Redis;
use ArrowWorker\Component\Channel\Queue;
use ArrowWorker\Client\Tcp\Client as Tcp;
use ArrowWorker\Library\Coroutine;
use ArrowWorker\Library\Process;
use ArrowWorker\Library\Channel as SwChan;
use Swoole\Runtime;


/**
 * Class Log
 * @package ArrowWorker
 */
class Log
{
	
	const TYPE_WARNING = 'Warning';
	
	const TYPE_NOTICE = 'Notice';
	
	const TYPE_DEBUG = 'Debug';
	
	const TYPE_ERROR = 'Error';
	
	const TYPE_EMERGENCY = 'Emergency';
	
	const TYPE_EXCEPTION = 'Exception';
	
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
	
	
	const MAX_BUFFER_SIZE = 1024;
	
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
	const MODULE_NAME = 'Log';
	
	private static $config = [];
	
	/**
	 * @var Log
	 */
	private static $instance;
	
	
	private static $processNum = 1;
	
	/**
	 * @var string
	 */
	private static $baseDir = APP_PATH . DIRECTORY_SEPARATOR . APP_RUNTIME_DIR . '/Log/';
	
	
	/**
	 * @var
	 */
	private static $stdoutFile;
	
	/**
	 * @var Queue
	 */
	private static $msgInstance;
	/**
	 * bufSize : log buffer size 10M
	 * @var int
	 */
	private static $bufSize = 10485760;
	
	/**
	 * msgSize : a single log size 1M
	 * @var int
	 */
	private static $msgSize = 1048576;
	
	
	/**
	 * chanSize : log buffer size 10M
	 * @var int
	 */
	private $chanSize = 1024000;
	
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
	 * @var array
	 */
	private $tcpClient = [];
	
	/**
	 * redis instance
	 * @var array
	 */
	private $_redisClient = [];
	
	/**
	 * @var SwChan;
	 */
	private $_toFileChan;
	
	/**
	 * @var SwChan
	 */
	private $_toTcpChan;
	
	/**
	 * @var SwChan
	 */
	private $_toRedisChan;
	
	/**
	 * @var array
	 */
	private $_fileHandlerMap = [];
	
	/**
	 * write log to file
	 * @var array
	 */
	private $writeType = [
		self::TO_FILE,
	];
	
	/**
	 * password of redis
	 * @var array
	 */
	private $tcpConfig = [
		'host' => '127.0.0.1',
		'port' => '6379',
	];
	
	/**
	 * @var array
	 */
	private $redisConfig = [
		'host'     => '127.0.0.1',
		'port'     => '6379',
		'queue'    => 'ArrowLog',
		'password' => '',
	];

	
	
	/**
	 * @var bool $isDemonize
	 */
	public static function Initialize()
	{
		self::initConfig();
		self::checkLogDir();
		self::resetStd();
		self::initMsgInstance();
		
	}
	
	
	public static function GetProcessNum() : int
	{
		return (int)self::$processNum;
	}
	
	public static function GetStdOutFilePath()
	{
		return self::$stdoutFile;
	}
	
	private function __construct()
	{
		$this->parseConfig();
		$this->initHandler();
		$this->initSignalHandler();
	}
	
	private static function checkLogDir()
	{
		if ( !is_dir( self::$baseDir ) )
		{
			if ( !mkdir( self::$baseDir, 0777, true ) )
			{
				self::DumpExit( 'creating log directory failed.' );
			}
		}
	}
	
	private static function initConfig()
	{
		$config = Config::Get( 'Log' );
		if ( !is_array( $config ) )
		{
			self::DumpExit( 'incorrect log config' );
			return;
		}
		self::$config     = $config;
		self::$processNum = $config[ 'process' ] ?? 1;
		self::$baseDir    = $config[ 'baseDir' ] ?? self::$baseDir;
		self::$stdoutFile = self::$baseDir . DIRECTORY_SEPARATOR . 'Arrow.log';
		
		self::$bufSize    = $config[ 'bufSize' ] ?? self::$bufSize;
		self::$msgSize   = $config[ 'chanSize' ] ?? self::$bufSize;
	}
	
	private function parseConfig()
	{
		$config = self::$config;
		$toTcp  = self::TO_TCP;
		if ( isset( $config[ $toTcp ] ) &&
		     isset( $config[ $toTcp ][ 'host' ] ) &&
		     isset( $config[ $toTcp ][ 'port' ] ) )
		{
			$config[ $toTcp ][ 'poolSize' ] = $config[ $toTcp ][ 'poolSize' ] ?? 10;
			$this->writeType[]              = $toTcp;
			$this->tcpConfig                = $config[ $toTcp ];
		}
		
		$toRedis = self::TO_REDIS;
		if ( isset( $config[ $toRedis ] ) &&
		     isset( $config[ $toRedis ][ 'host' ] ) &&
		     isset( $config[ $toRedis ][ 'port' ] ) &&
		     isset( $config[ $toRedis ][ 'password' ] ) &&
		     isset( $config[ $toRedis ][ 'queue' ] )
		)
		{
			$config[ $toRedis ][ 'poolSize' ] = $config[ $toRedis ][ 'poolSize' ] ?? 10;
			$this->writeType[]                = $toRedis;
			$this->redisConfig                = $config[ $toRedis ];
		}
		
		$this->chanSize = $config['chanSize'] ?? 1024000;
	}
	
	
	private function initHandler()
	{
		$this->_toFileChan = SwChan::Init( $this->chanSize );
		foreach ( $this->writeType as $type )
		{
			switch ( $type )
			{
				case self::TO_REDIS:
					
					for ( $i = 0; $i < $this->redisConfig[ 'poolSize' ]; $i++ )
					{
						$client = Redis::Init( [
							'host'     => $this->redisConfig[ 'host' ],
							'port'     => $this->redisConfig[ 'port' ],
							'password' => $this->redisConfig[ 'password' ],
						] );
						if ( $client->InitConnection() )
						{
							$this->_redisClient[] = $client;
						}
						else
						{
							if ( 0 == $i )
							{
								self::Dump( 'init redis client failed, config : ' .
								            json_encode( $this->redisConfig ), self::TYPE_WARNING, self::MODULE_NAME );
							}
						}
					}
					$this->_toRedisChan = SwChan::Init( $this->chanSize );
					
					break;
				
				case self::TO_TCP;
					
					$this->_toTcpChan = SwChan::Init( $this->chanSize );
					for ( $i = 0; $i < $this->tcpConfig[ 'poolSize' ]; $i++ )
					{
						$client = Tcp::Init( $this->tcpConfig[ 'host' ], $this->tcpConfig[ 'port' ] );
						if ( $client->IsConnected() )
						{
							$this->tcpClient[] = $client;
						}
						else
						{
							if ( 0 == $i )
							{
								Log::Dump( 'init tcp client failed. config : ' .
								           json_encode( $this->tcpConfig ), Log::TYPE_WARNING, self::MODULE_NAME );
							}
						}
					}
					
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
		self::Dump( $log, self::TYPE_EMERGENCY, self::MODULE_NAME );
		self::_fillLog( $log, $module, 'C' );
	}
	
	/**
	 * @param string $log
	 * @param string $module
	 * @param string $level
	 */
	private static function _fillLog( string $log, string $module = '', string $level = 'D' )
	{
		$time                                  = date( 'Y-m-d H:i:s' );
		Coroutine::GetContext()[ __CLASS__ ][] = [
			"{$level}�{$module}�{$time}",
			$log,
		];
	}
	
	/**
	 * Dump : echo log to standard output
	 * @param string $log
	 * @param string $type
	 * @param string $module
	 */
	public static function Dump( string $log, string $type = self::TYPE_DEBUG, string $module = 'Unknown' )
	{
		echo sprintf( "%s | %s | %s | %s " . PHP_EOL, self::_getTime(), $type, $module, $log );
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
	private static function initMsgInstance()
	{
		if ( !is_object( self::$msgInstance ) )
		{
			self::$msgInstance = Chan::Get(
				'log',
				[
					'msgSize' => self::$msgSize,
					'bufSize' => self::$bufSize,
				]
			);
		}
	}
	
	/**
	 * @param string $log
	 * @return array
	 */
	private function _parseModuleLevel( string $log )
	{
		$logInfo = explode( '�', $log );
		$level   = $logInfo[ 0 ];
		return [
			'level'  => $level,
			'module' => '' == $logInfo[ 1 ] ? self::DEFAULT_LOG_DIR : $logInfo[ 1 ],
			'body'   => substr( $log, strlen( $level . $logInfo[ 1 ] ) + 6 ),
		];
	}
	
	/**
	 * @param string $module
	 * @param string $level
	 * @param string $log
	 * @param string $date
	 * @return void
	 */
	private function _writeFile( string $module, string $level, string $log, string $date )
	{
		$alias = "{$module}{$level}{$date}";
		
		CHECK_FILE_HANDLER:
		if ( isset( $this->_fileHandlerMap[ $alias ] ) )
		{
			goto WRITE_LOG;
		}
		
		$fileRes = $this->_initFileHandler( $module, $this->_getFileName( $level, $date ) );
		if ( false === $fileRes )
		{
			goto CHECK_FILE_HANDLER;
		}
		$this->_fileHandlerMap[ $alias ] = $fileRes;
		
		WRITE_LOG:
		$result = Coroutine::FileWrite( $this->_fileHandlerMap[ $alias ], $log );
		if ( false === $result )
		{
			Log::Dump( "Coroutine::FileWrite failed, log : {$log}", self::TYPE_EMERGENCY, self::MODULE_NAME );
		}
		
	}
	
	/**
	 * @param string $fileDir
	 * @param string $fileExt
	 * @return bool|resource
	 */
	private function _initFileHandler( string $fileDir, string $fileExt )
	{
		$fileDir  = self::$baseDir . $fileDir;
		$filePath = "{$fileDir}/{$fileExt}";
		
		$checkDirTimes = 0;
		RE_CHECK_DIR:
		if ( !is_dir( $fileDir ) )
		{
			$checkDirTimes++;
			if ( !mkdir( $fileDir, 0766, true ) )
			{
				if ( $checkDirTimes > 2 )
				{
					Log::Dump( "make log directory:{$fileDir} failed", self::TYPE_EMERGENCY, self::MODULE_NAME );
					return false;
				}
				Coroutine::Sleep( 0.5 );
				goto RE_CHECK_DIR;
			}
		}
		
		$fileRes = fopen( $filePath, 'a' );
		if ( false === $fileRes )
		{
			Log::Dump( "fopen log file:{$filePath} failed", Log::TYPE_EMERGENCY, self::MODULE_NAME );
			return false;
		}
		return $fileRes;
	}
	
	
	/**
	 * @param string $level
	 * @param string $date
	 * @return string
	 */
	private function _getFileName( string $level, string $date )
	{
		switch ( $level )
		{
			case 'A':
				$ext = "Alert";
				break;
			case 'D':
				$ext = "Debug";
				break;
			case 'E':
				$ext = "Error";
				break;
			case 'W':
				$ext = "Warning";
				break;
			case 'N':
				$ext = "Notice";
				break;
			case 'C':
				$ext = "Critical";
				break;
			case 'EM':
				$ext = "Emergency";
				break;
			default:
				$ext = "Info";
		}
		return "{$date}.{$ext}.log";
	}
	
	/**
	 * Start : start log process
	 */
	public static function Start()
	{
		$log            = new  self();
		self::$instance = $log;
		$log->initCoroutine();
		$log->_exit();
	}
	
	private function initCoroutine()
	{
		Coroutine::Enable();
		for ( $i = 0; $i < 64; $i++ )
		{
			Coroutine::Create( function ()
			{
				$this->WriteToFile();
			} );
		}
		
		$tcpClientCount = count( $this->tcpClient );
		for ( $i = 0; $i < $tcpClientCount; $i++ )
		{
			Coroutine::Create( function () use ( $i )
			{
				$this->WriteToTcp( $i );
			} );
		}
		
		$redisClientCount = count( $this->_redisClient );
		for ( $i = 0; $i < $redisClientCount; $i++ )
		{
			Coroutine::Create( function () use ( $i )
			{
				$this->WriteToRedis( $i );
			} );
		}
		
		for ( $i = 0; $i < 2; $i++ )
		{
			Coroutine::Create( function ()
			{
				$this->Dispatch();
			} );
		}
		
		Coroutine::Create( function ()
		{
			while ( true )
			{
				if ( $this->_isTerminate )
				{
					break;
				}
				Coroutine::Sleep(0.2);
				pcntl_signal_dispatch();
				
			}
		} );
		
		Coroutine::Wait();
	}
	
	public function Dispatch()
	{
		$queue = self::$msgInstance;
		while ( true )
		{
			if (
				$this->_isTerminate &&
				$queue->Status()[ 'msg_qnum' ] == 0
			)
			{
				break;
			}
			
			$log = $queue->Read( 10000 );
			if ( $log === false )
			{
				continue;
			}
			
			if ( false == $this->_toFileChan->Push( $log, 1 ) )
			{
				Log::Dump( "push log chan failed, data:{$log}, error code： " .
				           $this->_toFileChan->GetErrorCode() .
				           "}", self::TYPE_WARNING, self::MODULE_NAME );
			}
			
			if ( in_array( self::TO_TCP, $this->writeType ) )
			{
				$this->_toTcpChan->Push( $log, 1 );
			}
			
			if ( in_array( self::TO_REDIS, $this->writeType ) )
			{
				$this->_toRedisChan->Push( $log, 1 );
			}
			
		}
		
		$this->_isTerminateChan = true;
		//self::Dump( self::MODULE_NAME.'dispatch coroutine exited' );
	}
	
	/**
	 *
	 */
	public function WriteToFile()
	{
		$buffer = [];
		$break  = true;
		$chan   = $this->_toFileChan;
		while ( true )
		{
			$data = $chan->Pop( 0.2 );
			if ( $this->_isTerminateChan && $data === false && $break )
			{
				break;
			}
			
			$date = date( 'Ymd' );
			if ( $data === false )
			{
				goto FLUSH;
			}
			
			$log       = $this->_parseModuleLevel( $data );
			$bufferKey = $log[ 'module' ] . $log[ 'level' ];
			if ( isset( $buffer[ $bufferKey ] ) )
			{
				$buffer[ $bufferKey ][ 'body' ] = $buffer[ $bufferKey ][ 'body' ] . $log[ 'body' ];
				$buffer[ $bufferKey ][ 'size' ] += strlen( $log[ 'body' ] );
			}
			else
			{
				$buffer[ $bufferKey ] = array_merge(
					$log,
					[
						'size'      => strlen( $log[ 'body' ] ),
						'module'    => $log[ 'module' ],
						'level'     => $log[ 'level' ],
						'flushTime' => time(),
					]
				);
			}
			
			FLUSH:
			$emptyBufferCount = 0;
			foreach ( $buffer as $eachBufKey => $eachBuffer )
			{
				if ( 0 == $eachBuffer[ 'size' ] )
				{
					$emptyBufferCount++;
					continue;
				}
				
				if ( time() - $eachBuffer[ 'flushTime' ] >= 2 || $eachBuffer[ 'size' ] >= self::MAX_BUFFER_SIZE )
				{
					$this->_writeFile( $eachBuffer[ 'module' ], $eachBuffer[ 'level' ], $eachBuffer[ 'body' ], $date );
					$buffer[ $eachBufKey ][ 'body' ]      = '';
					$buffer[ $eachBufKey ][ 'size' ]      = 0;
					$buffer[ $eachBufKey ][ 'flushTime' ] = time();
				}
			}
			$break = count( $buffer ) == $emptyBufferCount ? true : false;
		}
		//self::Dump( self::MODULE_NAME.'file-writing coroutine exited' );
	}
	
	/**
	 * @var int $clientIndex
	 */
	public function WriteToTcp( int $clientIndex )
	{
		while ( true )
		{
			$data = $this->_toTcpChan->Pop( 0.5 );
			if ( $this->_isTerminateChan && $data === false )
			{
				break;
			}
			
			if ( $data === false )
			{
				Coroutine::Sleep( 1 );
				continue;
			}
			
			if ( false == $this->tcpClient[ $clientIndex ]->Send( $data, 3 ) )
			{
				Log::Dump( " tcpClient[{$clientIndex}]->Send( {$data}, 3 ) failed", self::TYPE_WARNING, self::MODULE_NAME );
			}
		}
		//self::Dump( self::MODULE_NAME . ' [ Debug ] tcp-writing coroutine exited' );
	}
	
	/**
	 * @var int $clientIndex
	 */
	public function WriteToRedis( int $clientIndex )
	{
		$queue = $this->redisConfig[ 'queue' ];
		while ( true )
		{
			$data = $this->_toRedisChan->Pop( 0.5 );
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
				if ( false !== $this->_redisClient[ $clientIndex ]->Lpush( $queue, $data ) )
				{
					break;
				}
				Log::Dump( "redisClient[{$clientIndex}]->Lpush( {$queue}, {$data} ) failed", self::TYPE_WARNING, self::MODULE_NAME );
			}
			
		}
		
	}
	
	/**
	 * _exit : exit log process while there are no message in log queue
	 */
	private function _exit()
	{
		self::Dump( ' exited. queue status : ' .
		              json_encode( self::$msgInstance->Status() ), self::TYPE_DEBUG, self::MODULE_NAME );
		exit( 0 );
	}
	
	private static function resetStd()
	{
		if ( Console::Init()->IsDebug() )
		{
			return;
		}
		
		global $STDOUT, $STDERR;
		$newStdResource = fopen( self::$stdoutFile, "a" );
		if ( !is_resource( $newStdResource ) )
		{
			die( "ArrowWorker hint : can not open stdoutFile" . PHP_EOL );
		}
		
		fclose( STDOUT );
		fclose( STDERR );
		$STDOUT = fopen( self::$stdoutFile, 'a' );
		$STDERR = fopen( self::$stdoutFile, 'a' );
	}
	
	/**
	 * _setSignalHandler : set function for signal handler
	 * @author Louis
	 */
	private function initSignalHandler()
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
		$this->sendTcpHeartbeat();
		$this->cleanUselessFileHandler();
		pcntl_alarm( self::TCP_HEARTBEAT_PERIOD );
	}
	
	/**
	 *
	 */
	private function cleanUselessFileHandler()
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
	private function sendTcpHeartbeat()
	{
		foreach ($this->tcpClient as $client )
		{
			$client->Send( 'heartbeat' );
		}
	}
	
	/**
	 * @param string $logId
	 */
	public static function Init( string $logId = '' )
	{
		Coroutine::GetContext()[ __CLASS__ . '_id' ] = '' === $logId ? date( 'ymdHis' ) .
		                                                               Process::Id() . Coroutine::Id() .
		                                                               mt_rand( 100, 999 ) : $logId;
	}
	
	/**
	 * @return string
	 */
	public static function GetLogId() : string
	{
		return Coroutine::GetContext()[ __CLASS__ . '_id' ];
	}
	
	/**
	 *
	 */
	public static function Release()
	{
		$context = Coroutine::GetContext();
		if ( !isset( $context[ __CLASS__ ] ) )
		{
			return;
		}
		
		$msgObj = self::$msgInstance;
		$logId  = $context[ __CLASS__ . '_id' ];
		foreach ( $context[ __CLASS__ ] as $log )
		{
			$msgObj->Write( "{$log[0]} | {$logId} | $log[1]" . PHP_EOL );
		}
	}
	
}