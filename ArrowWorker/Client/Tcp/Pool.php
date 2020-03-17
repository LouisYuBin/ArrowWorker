<?php
/**
 * By yubin at 2019-10-05 11:05.
 */

namespace ArrowWorker\Client\Tcp;


use ArrowWorker\Config;
use ArrowWorker\Container;
use ArrowWorker\Log;
use ArrowWorker\Library\Coroutine;
use ArrowWorker\Library\Channel as SwChan;
use ArrowWorker\PoolInterface as ConnPool;

class Pool implements ConnPool
{

    const LOG_NAME    = 'TcpClient';

    const CONFIG_NAME = 'TcpClient';
    
    const MODULE_NAME = "[ TcpPool ] ";
    
    private $config = [];
    
    private $pool = [];
    
    private $container;
    
    /**
     * @var self $instance
     * */
    private static $instance;
    
    public function __construct(Container $container, array $aliasConfig, array $userConfig=[])
    {
    	self::$instance = $this;
    	$this->container = $container;
    	$this->initConfig($aliasConfig, $userConfig);
    	$this->initPool();
    }
	
	public function initConfig(array $aliasConfig, array $userConfig=[])
    {
	    if( count($userConfig)>0 )
	    {
	    	$config = $userConfig;
		    goto INIT;
	    }
	
	    $config = Config::Get( self::CONFIG_NAME );
	    if ( !is_array( $config ) || count( $config ) == 0 )
	    {
		    Log::Dump( 'load config file failed', Log::TYPE_WARNING, self::MODULE_NAME );
		    return ;
	    }
	
	    INIT:
	    foreach ( $config as $index => $value )
	    {
		    if( !isset($aliasConfig[$index]) )
		    {
			    continue ;
		    }
		
		    if (
			    !isset( $value['host'] ) ||
			    !isset( $value['port'] )
		    )
		    {
			    Log::Dump( "configuration for {$index} is incorrect. config : ".json_encode($value), Log::TYPE_WARNING,self::MODULE_NAME );
			    continue;
		    }
		
		    $value['poolSize']     = (int)$aliasConfig[$index]>0 ? $aliasConfig[$index] : self::DEFAULT_POOL_SIZE;
		    $value['connectedNum'] = 0;
		
		    $this->config[$index] = $value;
		    $this->pool[$index]   = $this->container->Make(SwChan::class, [ $this->container, $value['poolSize'] ] );
	    }
    }

    /**
     * initialize connection pool
     */
    public function InitPool()
    {
        foreach ($this->config as $index=>$config)
        {
            for ($i=$config['connectedNum']; $i<$config['poolSize']; $i++)
            {
                $conn = Client::Init( $config['host'], $config['port'] );
                if( false===$conn->IsConnected() )
                {
                    Log::Dump("initialize connection failed, config : {$index}=>".json_encode($config), Log::TYPE_WARNING, self::MODULE_NAME );
                    continue ;
                }
                $this->config[$index]['connectedNum']++;
	            $this->pool[$index]->Push( $conn );
            }
        }
    }

    /**
     * @param string $alias
     * @return false|Client
     */
    public static function Get( string $alias = 'default' )
    {
	    $class   = __CLASS__;
        $context = Coroutine::GetContext();
        
        if( isset($context[$class][$alias]) )
        {
            return $context[$class][$alias];
        }
        
        return self::$instance->getConnection($class, $alias);
    }
    
    private function getConnection(string $class, string $alias)
    {
	
	    if( !isset($pool->pool[$alias] ) )
	    {
		    return false;
	    }
	
	    $retryTimes = 0;
	    _RETRY:
	    $conn = $this->pool[$alias]->Pop( 0.2 );
	    if ( false === $conn )
	    {
		    if( $this->config[$alias]['connectedNum']<$this->config[$alias]['poolSize'] )
		    {
			    $this->initPool();
		    }
		
		    if( $retryTimes<=2 )
		    {
			    $retryTimes++;
			    Log::Warning("get ( {$alias} : {$retryTimes} ) connection failed.", [],self::LOG_NAME);
			    goto _RETRY;
		    }
	    }
	    $context[$class][$alias] = $conn;
	    return $conn;
    }

    /**
     * @return void
     */
    public function Release() : void
    {
    	$class   = __CLASS__;
	    $context = Coroutine::GetContext();
	
	    if( !isset($context[$class]) )
        {
            return ;
        }

        foreach ( $context[$class] as $alias=>$connection )
        {
	        $this->pool[$alias]->Push( $connection );
        }
    }

}