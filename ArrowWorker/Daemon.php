<?php

namespace ArrowWorker;

use ArrowWorker\Library\Process;

use ArrowWorker\Server\Tcp;
use ArrowWorker\Server\Http;
use ArrowWorker\Server\Ws;
use ArrowWorker\Server\Udp;

/**
 * Class Daemon : demonize process
 * @package ArrowWorker
 */
class Daemon
{
	
	/**
	 *
	 */
	const LOG_PREFIX = '[ Monitor ] ';
	
	/**
	 *
	 */
	const PROCESS_LOG = 'log';
	
	/**
	 *
	 */
	const PROCESS_TCP = 'Tcp';
	
	/**
	 *
	 */
	const PROCESS_UDP = 'Udp';
	
	/**
	 *
	 */
	const PROCESS_HTTP = 'Http';
	
	/**
	 *
	 */
	const PROCESS_WEBSOCKET = 'Ws';
	
	
	/**
	 * appName : application name for service
	 * @var mixed|string
	 */
	const APP_NAME = 'Arrow';
	
	const APP_SERVER = 'server';
	
	const APP_WORKER = 'worker';
	
	/**
	 * path of where pid file will be located
	 * @var string
	 */
	const PID_DIR = APP_PATH . DIRECTORY_SEPARATOR . APP_RUNTIME_DIR . '/Pid/';
	
	/**
	 * 需要去除的进程执行权限
	 * @var int
	 */
	private static $umask = 0;
	
	/**
	 * $pid : pid file for monitor process
	 * @var mixed|string
	 */
	const PID = self::PID_DIR . self::APP_NAME . '.pid';
	
	/**
	 * @var string
	 */
	public static $identity = '';
	
	private static $_isDebug = false;
	
	private static $_application = [];
	
	
	/**
	 * pidMap : child process name
	 * @var array
	 */
	private $_pidMap = [];
	
	/**
	 * terminate : is terminate process
	 * @var bool
	 */
	private $_terminate = false;
	
	
	public function __construct()
	{
		$this->_changeWorkDirectory();
		$this->_createPidFile();
		$this->_setProcessName( "started at " . date( "Y-m-d H:i:s" ) );
	}
	
	public static function Start( string $application, bool $isDebug = false )
	{
		set_time_limit( 0 );
		self::_initParameter( $application, $isDebug );
		self::_initFunction();
		self::_demonize();
		
		$daemon = new self();
		$daemon->_initComponent();
		$daemon->_setSignalHandler();
		$daemon->_startProcess();
		$daemon->_startMonitor();
	}
	
	
	public static function SetDemonize( bool $isDebug )
	{
		self::$_isDebug = $isDebug;
	}
	
	public static function SetStartApp( string $apps )
	{
		$appList = explode( ':', $apps );
		foreach ( $appList as $app )
		{
			$app = strtolower( $app );
			if ( !in_array( $app, [
				self::APP_SERVER,
				self::APP_WORKER,
			] ) )
			{
				continue;
			}
			self::$_application[] = $app;
		}
		
		if ( 0 == count( self::$_application ) )
		{
			self::$_application = [ self::APP_SERVER ];
		}
	}
	
	private function _initComponent()
	{
		Log::Initialize();
		Memory::Init();
	}
	
	private function _startProcess()
	{
		$this->_startLogProcess();
		
		rsort( self::$_application );
		foreach ( self::$_application as $appType )
		{
			if ( $appType == self::APP_SERVER )
			{
				$this->_startSwooleServer();
			}
			else if ( $appType == self::APP_WORKER )
			{
				$this->_startWorkerProcess();
			}
		}
		
	}
	
	private function _startLogProcess()
	{
		$processNUm = Log::GetProcessNum();
		for ( $i = 0; $i < $processNUm; $i++ )
		{
			$pid = Process::Fork();
			if ( $pid == 0 )
			{
				Log::Dump( static::LOG_PREFIX . 'starting log process ( ' . Process::Id() . ' )' );
				$this->_setProcessName( static::PROCESS_LOG );
				Log::Start();
			}
			else
			{
				$this->_pidMap[] = [
					'pid'   => $pid,
					'type'  => self::PROCESS_LOG,
					'index' => 0,
				];
			}
		}
		
	}
	
	private function _startWorkerProcess()
	{
		$pid = Process::Fork();
		if ( $pid == 0 )
		{
			Log::Dump( static::LOG_PREFIX . 'starting worker process( ' . Process::Id() . ' )' );
			$this->_setProcessName( 'Worker-group master' );
			Worker::Start();
		}
		else
		{
			$this->_pidMap[] = [
				'pid'   => $pid,
				'type'  => 'worker',
				'index' => 0,
			];
		}
	}
	
	
	/**
	 * @param int $pointedIndex
	 */
	private function _startSwooleServer( int $pointedIndex = 0 )
	{
		$configs = Config::Get( 'Server' );
		if ( false === $configs || !is_array( $configs ) )
		{
			return;
		}
		foreach ( $configs as $index => $config )
		{
			//必要配置不完整则不开启
			if ( !isset( $config[ 'type' ] ) ||
			     !isset( $config[ 'port' ] ) ||
			     !in_array( $config[ 'type' ], [
				     self::PROCESS_HTTP,
				     self::PROCESS_WEBSOCKET,
				     self::PROCESS_TCP,
				     self::PROCESS_UDP,
			     ] ) )
			{
				continue;
			}
			
			if ( $pointedIndex == 0 )  //start all swoole server
			{
				$this->_startPointedSwooleServer( $config, $index );
			}
			else            // start specified swoole server only
			{
				if ( $pointedIndex != $index )
				{
					continue;
				}
				$this->_startPointedSwooleServer( $config, $index );
			}
		}
	}
	
	/**
	 * @param array $config
	 * @param int   $index
	 */
	private function _startPointedSwooleServer( array $config, int $index )
	{
		$pid = Process::Fork();
		if ( $pid == 0 )
		{
			$pid                  = Process::Id();
			$config[ 'identity' ] = self::$identity;
			
			$processName = "{$config['type']}:{$config['port']} Master";
			Log::Dump( self::LOG_PREFIX . "starting {$processName} process ( $pid )" );
			$this->_setProcessName( $processName );
			if ( $config[ 'type' ] == self::PROCESS_HTTP )
			{
				Http::Start( $config );
			}
			else if ( $config[ 'type' ] == self::PROCESS_WEBSOCKET )
			{
				Ws::Start( $config );
			}
			else if ( $config[ 'type' ] == self::PROCESS_TCP )
			{
				Tcp::Start( $config );
			}
			else if ( $config[ 'type' ] == self::PROCESS_UDP )
			{
				Udp::Start( $config );
			}
			
			exit( 0 );
		}
		else
		{
			$this->_pidMap[] = [
				'pid'   => $pid,
				'index' => $index,
				'type'  => 'server',
			];
		}
	}
	
	private function _startMonitor()
	{
		Log::Dump( static::LOG_PREFIX . 'starting monitor process ( ' . Process::Id() . ' )' );
		while ( 1 )
		{
			if ( $this->_terminate )
			{
				//exit sequence: server -> worker -> log
				if ( $this->_exitWorkerProcess( 'server' ) )
				{
					if ( $this->_exitWorkerProcess( 'worker' ) )
					{
						$this->_exitWorkerProcess( 'log' );
					}
				}
				
				$this->_exitMonitor();
			}
			
			pcntl_signal_dispatch();
			
			$status = 0;
			//returns the process ID of the child which exited, -1 on error or zero if WNOHANG was provided as an option (on wait3-available systems) and no child was available
			$pid = Process::Wait( $status );
			$this->_handleExitedProcess( $pid, $status );
			pcntl_signal_dispatch();
			usleep( 100000 );
		}
	}
	
	/**
	 * _handleExitedProcess
	 * @param int $pid
	 * @param int $status
	 */
	private function _handleExitedProcess( int $pid, int $status )
	{
		foreach ( $this->_pidMap as $key => $eachProcess )
		{
			if ( $eachProcess[ 'pid' ] != $pid )
			{
				continue;
			}
			
			$appType = $eachProcess[ 'type' ];
			$index   = $eachProcess[ 'index' ];
			
			unset( $this->_pidMap[ $key ] );
			
			if ( $this->_terminate )
			{
				Log::Dump( self::LOG_PREFIX . "{$appType} process : {$pid} exited at status : {$status}" );
				return;
			}
			
			Log::Dump( self::LOG_PREFIX . "{$appType} process restarting at status {$status}" );
			
			if ( $appType == self::PROCESS_LOG )
			{
				$this->_startLogProcess();
			}
			else if ( $appType == 'worker' )
			{
				$this->_startWorkerProcess();
			}
			else if ( $appType == 'server' )
			{
				usleep( 100000 );
				$this->_startSwooleServer( $index );
			}
		}
	}
	
	
	/**
	 * @param string $type
	 * @return bool
	 */
	private function _exitWorkerProcess( string $type = 'server' )
	{
		$isExisted = true;
		foreach ( $this->_pidMap as $eachProcess )
		{
			if ( $eachProcess[ 'type' ] == $type )
			{
				$isExisted = false;
				$this->_exitProcess( $type, $eachProcess[ 'pid' ] );
			}
		}
		return $isExisted;
	}
	
	/**
	 * @param string $appType
	 * @param int    $pid
	 */
	private function _exitProcess( string $appType, int $pid )
	{
		$signal = SIGTERM;
		if ( !Process::IsKillNotified( (string)( $pid . $signal ) ) )
		{
			Log::Dump( self::LOG_PREFIX . "sending SIGTERM signal to {$appType}:{$pid} process" );
		}
		
		for ( $i = 0; $i < 3; $i++ )
		{
			if ( Process::Kill( $pid, $signal ) )
			{
				break;
			}
			usleep( 1000 );
		}
	}
	
	private function _exitMonitor()
	{
		if ( count( $this->_pidMap ) != 0 )
		{
			return;
		}
		
		if ( file_exists( self::PID ) )
		{
			unlink( self::PID );
		}
		
		Chan::Close();
		
		Log::Dump( static::LOG_PREFIX . 'exited' );
		exit( 0 );
	}
	
	/**
	 * @return int
	 */
	public static function GetPid() : int
	{
		if ( !file_exists( self::PID ) )
		{
			return 0;
		}
		
		return (int)file_get_contents( self::PID );
	}
	
	private static function _initParameter( string $application, bool $isDebug )
	{
		self::_initPid();
		self::SetStartApp( $application );
		self::SetDemonize( $isDebug );
	}
	
	private static function _initFunction()
	{
		if ( !function_exists( 'pcntl_signal_dispatch' ) )
		{
			declare( ticks=10 );
		}
		
		if ( !function_exists( 'pcntl_signal' ) )
		{
			Log::DumpExit( 'Arrow hint : php environment do not support pcntl_signal' );
		}
		
		if ( function_exists( 'gc_enable' ) )
		{
			gc_enable();
		}
		
	}
	
	private static function _demonize()
	{
		if ( self::$_isDebug )
		{
			goto SET_IDENTITY;
		}
		
		umask( self::$umask );
		
		if ( Process::Fork() != 0 )
		{
			exit();
		}
		
		posix_setsid();
		
		if ( Process::Fork() != 0 )
		{
			exit();
		}
		
		SET_IDENTITY:
		self::$identity = self::APP_NAME . '_' . Process::Id();
	}
	
	private function _createPidFile()
	{
		if ( !is_dir( self::PID_DIR ) )
		{
			mkdir( self::PID_DIR, 0766, true );
		}
		
		$fp = fopen( self::PID, 'w' ) or die( "cannot create pid file" . PHP_EOL );
		fwrite( $fp, Process::Id() );
		fclose( $fp );
	}
	
	private static function _initPid()
	{
		if ( !file_exists( self::PID ) )
		{
			return;
		}
		
		$pid = (int)file_get_contents( self::PID );
		
		if ( $pid > 0 && Process::Kill( $pid, 0 ) )
		{
			Log::Hint( "Arrow Hint : Server is already started." );
			exit;
		}
		else
		{
			unlink( self::PID );
		}
		
	}
	
	private function _changeWorkDirectory()
	{
		chdir( APP_PATH . DIRECTORY_SEPARATOR . APP_RUNTIME_DIR );
	}
	
	
	/**
	 * _setSignalHandler : set handle function for process signal
	 * @author Louis
	 */
	private function _setSignalHandler()
	{
		pcntl_signal( SIGCHLD, [
			$this,
			"SignalHandler",
		], false );
		pcntl_signal( SIGTERM, [
			$this,
			"SignalHandler",
		], false );
		pcntl_signal( SIGINT, [
			$this,
			"SignalHandler",
		], false );
		pcntl_signal( SIGQUIT, [
			$this,
			"SignalHandler",
		], false );
		// SIGTSTP have to be ignored on mac os
		pcntl_signal( SIGTSTP, SIG_IGN, false );
		
	}
	
	
	/**
	 * SignalHandler : handle process signal
	 * @param int $signal
	 * @return bool
	 * @author Louis
	 */
	public function SignalHandler( int $signal )
	{
		//Log::Dump(static::LOG_PREFIX."got a signal {$signal} : ".Process::SignalName($signal));
		switch ( $signal )
		{
			case SIGUSR1:
				$this->_terminate = true;
				break;
			case SIGTERM:
			case SIGINT:
			case SIGQUIT:
				$this->_terminate = true;
				break;
			default:
				return false;
		}
		return false;
	}
	
	
	/**
	 * _setProcessName  set process name
	 * @param string $proName
	 * @author Louis
	 */
	private function _setProcessName( string $proName )
	{
		Process::SetName( self::$identity . '_' . $proName );
	}
	
}