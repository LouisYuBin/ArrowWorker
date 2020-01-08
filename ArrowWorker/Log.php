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
	
	
	const MAX_BUFFER_SIZE = 4096;
	
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
	
	/**
	 * @var int
	 */
	const  CHAN_SIZE = 204800;
	
	
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
	 * directory for store log files
	 * @var string
	 */
	private static $baseDir = APP_PATH . DIRECTORY_SEPARATOR . APP_RUNTIME_DIR . '/Log/';
	
	/**
	 * write log to file
	 * @var array
	 */
	private static $toTypes = [
		'file',
	];
	
	/**
	 * password of redis
	 * @var array
	 */
	private static $tcpConfig = [
		'host' => '127.0.0.1',
		'port' => '6379',
	];
	
	/**
	 * @var array
	 */
	private static $redisConfig = [
		'host'     => '127.0.0.1',
		'port'     => '6379',
		'queue'    => 'ArrowLog',
		'password' => '',
	];
	
	/**
	 * @var
	 */
	private static $stdoutFile;
	
	/**
	 * @var Queue
	 */
	private static $msgInstance;
	
	private static $processNum = 1;
	
	/**
	 * Whether to close the log process
	 * @var bool
	 */
	private $isTerminate = false;
	
	/**
	 * Whether to close the log channel
	 * @var bool
	 */
	private $isTerminateChan = false;
	
	/**
	 *
	 * @var array
	 */
	private $tcpClient = [];
	
	/**
	 * redis instance
	 * @var array
	 */
	private $redisClient = [];
	
	/**
	 * @var SwChan;
	 */
	private $toFileChan;
	
	/**
	 * @var SwChan
	 */
	private $toTcpChan;
	
	/**
	 * @var SwChan
	 */
	private $toRedisChan;
	
	/**
	 * @var array
	 */
	private $fileHandlerMap = [];
	
	
	/**
	 * @var bool $isDemonize
	 */
	public static function Initialize()
	{
		self::initConfig();
		self::checkDir();
		self::initMsgInstance();
		self::resetStd();
	}
	
	public static function GetStdOutFilePath()
	{
		return self::$stdoutFile;
	}
	
	public static function GetProcessNum() : int
	{
		return (int)self::$processNum;
	}
	
	private function __construct()
	{
		$this->initHandler();
		$this->initSignalHandler();
	}
	
	private static function checkDir()
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
		if ( false === $config )
		{
			return;
		}
		
		self::$toTypes = [ self::TO_FILE ];
		$toTcp         = self::TO_TCP;
		if ( isset( $config[ $toTcp ] ) &&
		     isset( $config[ $toTcp ][ 'host' ] ) &&
		     isset( $config[ $toTcp ][ 'port' ] ) )
		{
			$config[ $toTcp ][ 'poolSize' ] = $config[ $toTcp ][ 'poolSize' ] ?? 10;
			self::$toTypes[]                = $toTcp;
			self::$tcpConfig                = $config[ $toTcp ];
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
			self::$toTypes[]                  = $toRedis;
			self::$redisConfig                = $config[ $toRedis ];
		}
		
		self::$bufSize    = $config[ 'bufSize' ] ?? self::$bufSize;
		self::$baseDir    = $config[ 'baseDir' ] ?? self::$baseDir;
		self::$stdoutFile = self::$baseDir . DIRECTORY_SEPARATOR . 'Arrow.log';
		self::$processNum = $config[ 'process' ] ?? 1;
	}
	
	
	private function initHandler()
	{
		$this->toFileChan = SwChan::Init( self::CHAN_SIZE );
		
		foreach ( self::$toTypes as $type )
		{
			switch ( $type )
			{
				case self::TO_REDIS:
					
					$config = self::$redisConfig;
					for ( $i = 0; $i < $config[ 'poolSize' ]; $i++ )
					{
						$client = Redis::Init( [
							'host'     => $config[ 'host' ],
							'port'     => $config[ 'port' ],
							'password' => $config[ 'password' ],
						] );
						if ( $client->InitConnection() )
						{
							$this->redisClient[] = $client;
						}
						else
						{
							if ( 0 == $i )
							{
								self::Dump( 'init redis client failed, config : ' .
								            json_encode( $config ), self::TYPE_WARNING, self::MODULE_NAME );
							}
						}
					}
					$this->toRedisChan = SwChan::Init( self::CHAN_SIZE );
					
					break;
				
				case self::TO_TCP;
					
					$this->toTcpChan = SwChan::Init( self::CHAN_SIZE );
					$config          = self::$tcpConfig;
					for ( $i = 0; $i < $config[ 'poolSize' ]; $i++ )
					{
						$client = Tcp::Init( $config[ 'host' ], $config[ 'port' ] );
						if ( $client->IsConnected() )
						{
							$this->tcpClient[] = $client;
						}
						else
						{
							if ( 0 == $i )
							{
								Log::Dump( 'init tcp client failed. config : ' .
								           json_encode( $config ), Log::TYPE_WARNING, self::MODULE_NAME );
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
	 * @param array  $context
	 * @param string $module
	 * @return void
	 */
	public static function Info( string $log, array $context = [], string $module = '' )
	{
		self::rebuildLog( $log, $context, $module, 'I' );
	}
	
	/**
	 * Info write an information log
	 * @param string $log
	 * @param array  $context
	 * @param string $module
	 * @return void
	 */
	public static function Alert( string $log, array $context = [], string $module = '' )
	{
		self::rebuildLog( $log, $context, $module, 'A' );
	}
	
	/**
	 * @param string $log
	 * @param array  $context
	 * @param string $module
	 * @return void
	 */
	public static function Debug( string $log, array $context = [], string $module = '' )
	{
		self::rebuildLog( $log, $context, $module, 'D' );
	}
	
	/**
	 * Notice : write an notice log
	 * @param string $log
	 * @param array  $context
	 * @param string $module
	 * @return void
	 */
	public static function Notice( string $log, array $context = [], string $module = '' )
	{
		self::rebuildLog( $log, $context, $module, 'N' );
	}
	
	/**
	 * Warning : write an warning log
	 * @param string $log
	 * @param array  $context
	 * @param string $module
	 * @return void
	 */
	public static function Warning( string $log, array $context = [], string $module = '' )
	{
		self::rebuildLog( $log, $context, $module, 'W' );
	}
	
	/**
	 * Error : write an error log
	 * @param string $log
	 * @param array  $context
	 * @param string $module
	 * @return void
	 */
	public static function Error( string $log, array $context = [], string $module = '' )
	{
		self::rebuildLog( $log, $context, $module, 'E' );
	}
	
	/**
	 * Emergency : write an Emergency log
	 * @param string $log
	 * @param array  $context
	 * @param string $module
	 * @return void
	 */
	public static function Emergency( string $log, array $context = [], string $module = '' )
	{
		self::rebuildLog( $log, $context, $module, 'EM' );
	}
	
	/**
	 * Critical : write a Critical log
	 * @param string $log
	 * @param array  $context
	 * @param string $module
	 * @return void
	 */
	public static function Critical( string $log, array $context = [], string $module = '' )
	{
		self::Dump( $log, self::TYPE_EMERGENCY, self::MODULE_NAME );
		self::rebuildLog( $log, $context, $module, 'C' );
	}
	
	/**
	 * @param string $log
	 * @param array  $context
	 * @param string $module
	 * @param string $level
	 */
	private static function rebuildLog( string $log, array $context = [], string $module = '', string $level = 'D' )
	{
		Coroutine::GetContext()[ __CLASS__ ][] = [
			$level,
			$module,
			date( 'Y-m-d H:i:s' ),
			$log,
			$context,
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
		echo sprintf( "%s | %s | %s | %s " . PHP_EOL, self::getTime(), $type, $module, $log );
	}
	
	/**
	 * @return false|string
	 */
	private static function getTime()
	{
		return date( 'Y-m-d H:i:s' );
	}
	
	/**
	 * Dump : echo log to standard output
	 * @param string $log
	 */
	public static function DumpExit( string $log )
	{
		echo( PHP_EOL . static::getTime() . ' ' . $log . PHP_EOL );
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
	private function parseModuleLevel( string $log )
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
	private function writeFile( string $module, string $level, string $log, string $date )
	{
		$alias = "{$module}{$level}{$date}";
		
		CHECK_FILE_HANDLER:
		if ( isset( $this->fileHandlerMap[ $alias ] ) )
		{
			goto WRITE_LOG;
		}
		
		$fileRes = $this->initFileHandler( $module, $this->getFileName( $level, $date ) );
		if ( false === $fileRes )
		{
			goto CHECK_FILE_HANDLER;
		}
		$this->fileHandlerMap[ $alias ] = $fileRes;
		
		WRITE_LOG:
		$result = Coroutine::FileWrite( $this->fileHandlerMap[ $alias ], $log );
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
	private function initFileHandler( string $fileDir, string $fileExt )
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
	private function getFileName( string $level, string $date )
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
		$log = new self();
		$log->initCoroutine();
		$log->exit();
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
		
		$redisClientCount = count( $this->redisClient );
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
				if ( $this->isTerminate )
				{
					break;
				}
				Coroutine::Sleep( 0.2 );
				pcntl_signal_dispatch();
			}
		} );
		Coroutine::Wait();
	}
	
	public function Dispatch()
	{
		$msgQueue = self::$msgInstance;
		$toTypes  = static::$toTypes;
		while ( true )
		{
			if (
				$this->isTerminate &&
				$msgQueue->Status()[ 'msg_qnum' ] == 0
			)
			{
				break;
			}
			
			$log = $msgQueue->Read( 10000 );
			if ( $log === false )
			{
				continue;
			}
			
			if ( false == $this->toFileChan->Push( $log, 1 ) )
			{
				Log::Dump( "push log chan failed, data:" . json_encode( $log ) . ", error code： " .
				           $this->toFileChan->GetErrorCode() .
				           "}", self::TYPE_WARNING, self::MODULE_NAME );
			}
			
			if ( in_array( self::TO_TCP, $toTypes ) )
			{
				$this->toTcpChan->Push( $log, 1 );
			}
			
			if ( in_array( self::TO_REDIS, $toTypes ) )
			{
				$this->toRedisChan->Push( $log, 1 );
			}
			
		}
		
		$this->isTerminateChan = true;
		//self::Dump( self::MODULE_NAME.'dispatch coroutine exited' );
	}
	
	/**
	 *
	 */
	public function WriteToFile()
	{
		$buffer = [];
		$break  = true;
		while ( true )
		{
			$data = $this->toFileChan->Pop( 0.2 );
			if ( $this->isTerminateChan && $data === false && $break )
			{
				break;
			}
			
			$date = date( 'Ymd' );
			if ( $data === false )
			{
				goto FLUSH;
			}
			
			[
				$level,
				$module,
				$time,
				$message,
				$context,
				$id,
			] = $data;
			$body      = "{$time} | {$id} | ".$this->parseLog( $message, $context );
			$bufferKey = $module . $level;
			if ( isset( $buffer[ $bufferKey ] ) )
			{
				$buffer[ $bufferKey ][ 'body' ] = $buffer[ $bufferKey ][ 'body' ] . $body;
				$buffer[ $bufferKey ][ 'size' ] += strlen( $body );
			}
			else
			{
				$buffer[ $bufferKey ] = array_merge(
					[
						'body'      => $body,
						'size'      => strlen( $body ),
						'module'    => $module,
						'level'     => $level,
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
					$this->writeFile( $eachBuffer[ 'module' ], $eachBuffer[ 'level' ], $eachBuffer[ 'body' ], $date );
					$buffer[ $eachBufKey ][ 'body' ]      = '';
					$buffer[ $eachBufKey ][ 'size' ]      = 0;
					$buffer[ $eachBufKey ][ 'flushTime' ] = time();
				}
			}
			$break = count( $buffer ) == $emptyBufferCount ? true : false;
		}
		//self::Dump( self::MODULE_NAME.'file-writing coroutine exited' );
	}
	
	private function parseLog( string $message, array $context = [] ) : string
	{
		$replace = [];
		foreach ( $context as $key => $val )
		{
			if ( is_string( $val ) )
			{
				$replace[ "{{$key}}" ] = $val;
			}
		}
		return strtr( $message, $replace );
	}
	
	/**
	 * @var int $clientIndex
	 */
	public function WriteToTcp( int $clientIndex )
	{
		while ( true )
		{
			$data = $this->toTcpChan->Pop( 0.5 );
			if ( $this->isTerminateChan && $data === false )
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
		$queue = self::$redisConfig[ 'queue' ];
		while ( true )
		{
			$data = $this->toRedisChan->Pop( 0.5 );
			if ( $this->isTerminateChan && $data === false )
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
				if ( false !== $this->redisClient[ $clientIndex ]->Lpush( $queue, $data ) )
				{
					break;
				}
				Log::Dump( "redisClient[{$clientIndex}]->Lpush( {$queue}, {$data} ) failed", self::TYPE_WARNING, self::MODULE_NAME );
			}
			
		}
		
	}
	
	/**
	 * exit : exit log process while there are no message in log queue
	 */
	private function exit()
	{
		static::Dump( ' exited. queue status : ' .
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
		$newStdResource = fopen( static::$stdoutFile, "a" );
		if ( !is_resource( $newStdResource ) )
		{
			die( "ArrowWorker hint : can not open stdoutFile" . PHP_EOL );
		}
		
		fclose( STDOUT );
		fclose( STDERR );
		$STDOUT = fopen( static::$stdoutFile, 'a' );
		$STDERR = fopen( static::$stdoutFile, 'a' );
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
				$this->handleAlarm();
				break;
			case SIGTERM:
				$this->isTerminate = true;
				break;
			default:
		}
	}
	
	/**
	 * handle log process alarm signal
	 */
	private function handleAlarm()
	{
		self::sendTcpHeartbeat();
		self::cleanUselessFileHandler();
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
		foreach ( $this->fileHandlerMap as $alias => $handler )
		{
			$aliasDate = substr( $alias, strlen( $alias ) - 8, 8 );
			if ( $today != $aliasDate )
			{
				fclose( $this->fileHandlerMap[ $alias ] );
				unset( $this->fileHandlerMap[ $alias ] );
				Log::Debug( "log file handler : {$alias} was cleaned.", [], self::LOG_NAME );
			}
		}
	}
	
	/**
	 *
	 */
	private function sendTcpHeartbeat()
	{
		foreach ( $this->tcpClient as $client )
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
			$log[6] = $logId;
			$msgObj->Write( $log );
		}
	}
	
}