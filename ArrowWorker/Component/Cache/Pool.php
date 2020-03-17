<?php

namespace ArrowWorker\Component\Cache;

use ArrowWorker\Container;
use ArrowWorker\Library\Channel as SwChan;

use ArrowWorker\Config;
use ArrowWorker\Log;
use ArrowWorker\Library\Coroutine;
use ArrowWorker\PoolInterface as ConnPool;

class Pool implements ConnPool
{
	
	const LOG_NAME = 'Cache';
	
	const MODULE_NAME = "CachePool";
	
	
	const CONFIG_NAME = 'Cache';
	
	
	const DEFAULT_DRIVER = 'Redis';
	
	private $drivers = [
		'REDIS' => Redis::class,
	];
	
	/**
	 * @var array
	 */
	private $pool = [];
	
	/**
	 * @var array
	 */
	private $config = [];
	
	private $container;
	
	private static $instance;
	
	public function __construct( Container $container, array $presetConfig, array $userConfig = [] )
	{
		self::$instance = $this;
		$this->container = $container;
		$this->initConfig( $presetConfig, $userConfig );
		$this->initPool();
	}
	
	/**
	 * @param array $presetConfig
	 * @param array $userConfig
	 */
	public function initConfig( array $presetConfig, array $userConfig = [] )
	{
		if ( count( $userConfig ) > 0 )
		{
			$config = $userConfig;
			goto INIT;
		}
		
		$config = Config::Get( self::CONFIG_NAME );
		if ( !is_array( $config ) || count( $config ) == 0 )
		{
			Log::Dump( 'incorrect config file', Log::TYPE_WARNING, self::MODULE_NAME );
			return;
		}
		
		INIT:
		foreach ( $config as $index => $value )
		{
			if ( !isset( $presetConfig[ $index ] ) )
			{
				continue;
			}
			
			if (
				!isset( $value[ 'driver' ] ) ||
				!isset( $this->drivers[ strtoupper($value[ 'driver' ])] ) ||
				!isset( $value[ 'host' ] ) ||
				!isset( $value[ 'port' ] ) ||
				!isset( $value[ 'password' ] )
			)
			{
				Log::Dump( __CLASS__ .
				           '::' .
				           __FUNCTION__ .
				           "incorrect configuration . {$index}=>" .
				           json_encode( $value ), Log::TYPE_WARNING, self::MODULE_NAME );
				continue;
			}
			
			$value['driver']         = strtoupper($value['driver']);
			$value[ 'poolSize' ]     = (int)$presetConfig[ $index ] > 0 ? $presetConfig[ $index ] : self::DEFAULT_POOL_SIZE;
			$value[ 'connectedNum' ] = 0;
			
			$this->config[ $index ] = $value;
			$this->pool[ $index ]   = $this->container->Make( SwChan::class, [ $this->container, $value[ 'poolSize' ] ] );
			
		}
	}
	
	
	/**
	 * initialize connection pool
	 */
	public function initPool()
	{
		foreach ( $this->config as $index => $config )
		{
			for ( $i = $config[ 'connectedNum' ]; $i < $config[ 'poolSize' ]; $i++ )
			{
				$conn = $this->container->Make( $this->drivers[ $config[ 'driver' ] ], [ $this->container, $config ] );
				if ( false === $conn->InitConnection() )
				{
					Log::Dump( __CLASS__ .
					           '::' .
					           __FUNCTION__ .
					           " InitConnection failed, config : {$index}=>" .
					           json_encode( $config ), Log::TYPE_WARNING, self::MODULE_NAME );
					continue;
				}
				$this->config[ $index ][ 'connectedNum' ]++;
				$this->pool[ $index ]->Push( $conn );
			}
		}
	}
	
	/**
	 * @param string $alias
	 * @return false|Redis
	 */
	public static function Get( string $alias = 'default' )
	{
		$class   = __CLASS__;
		$context = Coroutine::GetContext();
		if ( isset( $context[ $class ][ $alias ] ) )
		{
			return $context[ $class ][ $alias ];
		}
		
		return self::$instance->getConnection($class, $alias);
		
	}
	
	private function getConnection( string $class, string $alias )
	{
		if ( !isset( $pool->pool[ $alias ] ) )
		{
			return false;
		}
		
		$retryTimes = 0;
		_RETRY:
		$conn = $this->pool[ $alias ]->Pop( 0.2 );
		if ( false === $conn )
		{
			if ( $this->config[ $alias ][ 'connectedNum' ] < $this->config[ $alias ][ 'poolSize' ] )
			{
				$this->initPool();
			}
			
			if ( $retryTimes <= 2 )
			{
				$retryTimes++;
				Log::Dump( __CLASS__ .
				           '::' .
				           __FUNCTION__ .
				           " get connection( {$alias} : {$retryTimes} ) failed,retrying...", Log::TYPE_WARNING, self::MODULE_NAME );
				goto _RETRY;
			}
			
		}
		$context[ $class ][ $alias ] = $conn;
		return $conn;
	}
	
	/**
	 * @return void
	 */
	public function Release() : void
	{
		$class   = __CLASS__;
		$context = Coroutine::GetContext();
		if ( !isset( $context[ $class ] ) )
		{
			return;
		}
		
		foreach ( $context[ $class ] as $alias => $connection )
		{
			$this->pool[ $alias ]->Push( $connection );
		}
	}
	
}