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
	
	private static $_coBuffer = [];
	
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
	private $_tcpClient = [];
	
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
	 * @var bool $isDemonize
	 */
	public static function Initialize()
	{
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
		
		self::$_writeType = [ self::TO_FILE ];
		$toTcp            = self::TO_TCP;
		if ( isset( $config[ $toTcp ] ) &&
		     isset( $config[ $toTcp ][ 'host' ] ) &&
		     isset( $config[ $toTcp ][ 'port' ] ) )
		{
			$config[ $toTcp ]['poolSize'] = $config[ $toTcp ]['poolSize'] ?? 10;
			self::$_writeType[] = $toTcp;
			self::$_tcpConfig   = $config[ $toTcp ];
		}
		
		$toRedis = self::TO_REDIS;
		if ( isset( $config[ $toRedis ] ) &&
		     isset( $config[ $toRedis ][ 'host' ] ) &&
		     isset( $config[ $toRedis ][ 'port' ] ) &&
		     isset( $config[ $toRedis ][ 'password' ] ) &&
		     isset( $config[ $toRedis ][ 'queue' ] )
		)
		{
			$config[ $toRedis ]['poolSize'] = $config[ $toRedis ]['poolSize'] ?? 10;
			self::$_writeType[] = $toRedis;
			self::$_redisConfig = $config[ $toRedis ];
		}
		
		self::$_bufSize   = $config[ 'bufSize' ] ?? self::$_bufSize;
		self::$_chanSize  = $config[ 'chanSize' ] ?? self::$_bufSize;
		self::$_baseDir   = $config[ 'baseDir' ] ?? self::$_baseDir;
		self::$StdoutFile = self::$_baseDir . DIRECTORY_SEPARATOR . 'Arrow.log';
	}
	
	
	private function _initHandler()
	{
		$this->_toFileChan = SwChan::Init( self::$_chanSize );
		
		foreach ( self::$_writeType as $type )
		{
			switch ( $type )
			{
				case self::TO_REDIS:
					
					$config = self::$_redisConfig;
					for ($i=0; $i<$config[ 'poolSize' ]; $i++)
					{
						$client =  Redis::Init( [
							'host'     => $config[ 'host' ],
							'port'     => $config[ 'port' ],
							'password' => $config[ 'password' ],
						] );
						if( $client->InitConnection() )
						{
							$this->_redisClient[] = $client;
						}
						else
						{
							if( 0==$i )
							{
								self::Dump(self::LOG_PREFIX.'init redis client failed, config : '.json_encode($config));
							}
						}
					}
					$this->_toRedisChan = SwChan::Init( self::$_chanSize );
					
					break;
				
				case self::TO_TCP;
					
					$this->_toTcpChan = SwChan::Init( self::$_chanSize );
					$config = self::$_tcpConfig;
					for ( $i=0; $i<$config['poolSize']; $i++ )
					{
						$client = Tcp::Init( $config[ 'host' ], $config[ 'port' ] );
						if( $client->IsConnected() )
						{
							$this->_tcpClient[] = $client;
						}
						else
						{
							if( 0==$i )
							{
								Log::Dump( self::LOG_PREFIX.'init tcp client failed. config : '.json_encode($config));
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
		$time                                 = date( 'Y-m-d H:i:s' );
		self::$_coBuffer[ Coroutine::Id() ][] = [
			"{$level}�{$module}�{$time}",
			$log,
		];
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
			Log::Dump( self::LOG_PREFIX . " [ Emergency ] Coroutine::FileWrite failed, log : {$log}" );
		}
		
	}
	
	/**
	 * @param string $fileDir
	 * @param string $fileExt
	 * @return bool|resource
	 */
	private function _initFileHandler( string $fileDir, string $fileExt )
	{
		$fileDir  = self::$_baseDir . $fileDir;
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
					Log::Dump( self::LOG_PREFIX . " [ EMERGENCY ] make log directory:{$fileDir} failed . " );
					return false;
				}
				Coroutine::Sleep( 0.5 );
				goto RE_CHECK_DIR;
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
		$log = new self();
		$log->_initCoroutine();
		$log->_exit();
	}
	
	private function _initCoroutine()
	{
		Coroutine::Enable();
		for ( $i = 0; $i < 64; $i++ )
		{
			Coroutine::Create( function ()
			{
				$this->WriteToFile();
			} );
		}
		
		$tcpClientCount = count($this->_tcpClient);
		for ( $i=0; $i<$tcpClientCount; $i++ )
		{
			Coroutine::Create( function () use ($i)
			{
				$this->WriteToTcp($i);
			} );
		}
		
		$redisClientCount = count($this->_redisClient);
		for ( $i=0; $i<$redisClientCount; $i++ )
		{
			Coroutine::Create( function () use ($i)
			{
				$this->WriteToRedis($i);
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
			
			if ( false == $this->_toFileChan->Push( $log, 1 ) )
			{
				Log::Dump( "Push log chan failed, data:{$log}, error code： " .
				           $this->_toFileChan->GetErrorCode() .
				           "}" );
			}
			
			if ( in_array( static::TO_TCP, static::$_writeType ) )
			{
				$this->_toTcpChan->Push( $log, 1 );
			}
			
			if ( in_array( static::TO_REDIS, static::$_writeType ) )
			{
				$this->_toRedisChan->Push( $log, 1 );
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
		$buffer = [];
		$break  = true;
		while ( true )
		{
			$data = $this->_toFileChan->Pop( 0.2 );
			if ( $this->_isTerminateChan && $data === false && $break )
			{
				break;
			}
			
			if ( $data === false )
			{
				goto FLUSH;
			}
			
			$date      = date( 'Ymd' );
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
		//self::Dump( self::LOG_PREFIX.'file-writing coroutine exited' );
	}
	
	/**
	 * @var int $clientIndex
	 */
	public function WriteToTcp(int $clientIndex)
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
			
			if ( false == $this->_tcpClient[$clientIndex]->Send( $data, 3 ) )
			{
				Log::Dump( self::LOG_PREFIX . "tcpClient[{$clientIndex}]->Send( {$data}, 3 ) failed" );
			}
		}
		self::Dump( self::LOG_PREFIX . 'tcp-writing coroutine exited' );
	}
	
	/**
	 * @var int $clientIndex
	 */
	public function WriteToRedis(int $clientIndex)
	{
		$queue = self::$_redisConfig['queue'];
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
				if ( false !== $this->_redisClient[$clientIndex]->Lpush( $queue, $data ) )
				{
					break;
				}
				Log::Dump(self::LOG_PREFIX."redisClient[{$clientIndex}]->Lpush( {$queue}, {$data} ) failed");
			}
			
		}
		
	}
	
	/**
	 * _exit : exit log process while there are no message in log queue
	 */
	private function _exit()
	{
		static::Dump( self::LOG_PREFIX . ' exited. queue status : ' . json_encode( self::$_msgObject->Status() ) );
		exit( 0 );
	}
	
	private static function _resetStd()
	{
		if ( Console::Init()->IsDebug() )
		{
			return;
		}
		
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
		$coId                     = Coroutine::Id();
		self::$_logId[ $coId ]    = '' === $logId ? date( 'ymdHis' ) .
		                                            Process::Id() .
		                                            $coId .
		                                            mt_rand( 100, 999 ) : $logId;
		self::$_coBuffer[ $coId ] = [];
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
		$coId   = Coroutine::Id();
		$msgObj = self::$_msgObject;
		$logId  = self::$_logId[ Coroutine::Id() ];
		foreach ( self::$_coBuffer[ $coId ] as $log )
		{
			$msgObj->Write( "{$log[0]} | {$logId} | $log[1]" . PHP_EOL );
		}
		unset( self::$_logId[ $coId ], self::$_coBuffer[ $coId ] );
	}
	
}