<?php
/**
 * Project ArrowWorker
 * User:   louis
 * Date:   17-12-31
 */

namespace ArrowWorker\Web;

use ArrowWorker\Component\Cache\Pool;
use ArrowWorker\Config;
use ArrowWorker\Container;
use ArrowWorker\Log;


/**
 * Class Session
 * @package ArrowWorker
 */
class Session
{

    const MODULE_NAME = 'Session';

    const DEFAULT_TOKEN_KEY = 'token';

    /**
     * @var array
     */
    private static $config = [];

    private static $instance;
	
	private $container;
	
	private $pool;
	
	
	public function __construct(Container $container)
    {
    	self::$instance = $this;
    	$this->container = $container;
    	$this->initConfig();
    	$this->initPool();
    }
	
	private function initPool()
    {
        foreach ( self::$config as $host => $config )
        {
            $config[ 'driver' ] = 'Redis';
            $this->pool = $this->container->Get(Pool::class, [ [ $host => $config[ 'poolSize' ] ], [ $host => $config ] ] );
        }
    }

    private function initConfig()
    {
        $config = Config::Get( self::MODULE_NAME );
        if ( !is_array( $config ) )
        {
            Log::Dump( 'initialize config failed', Log::TYPE_WARNING, self::MODULE_NAME );
            return;
        }
        $this->config = $this->parseConfig( $config );
    }

    /**
     * @param array $configs
     * @return array
     */
    private function parseConfig( array $configs ) : array
    {
        $parsedConfig = [];
        foreach ( $configs as $serverNames => $config )
        {
            if (
                !isset( $config[ 'host' ] ) ||
                !isset( $config[ 'port' ] ) ||
                !isset( $config[ 'password' ] ) ||
                !isset( $config[ 'poolSize' ] ) ||
                !isset( $config[ 'tokenKey' ] ) ||
                !isset( $config[ 'tokenFrom' ] ) ||
                !in_array( $config[ 'tokenFrom' ], [
                    'get',
                    'post',
                    'cookie',
                ] )
            )
            {
                Log::Dump( "{$serverNames} config incorrect : " . json_encode( $config ), Log::TYPE_WARNING, self::MODULE_NAME );
                continue;
            }
            $config['tokenFrom'] = ucfirst($config['tokenFrom']);
            $serverNameList = explode(',', $serverNames);
            foreach ($serverNameList as $serverName)
            {
                $parsedConfig[ trim($serverName) ] = $config;
            }
        }
        return $parsedConfig;
    }


    private function getResource()
    {
        $token = self::GetToken();
        if( ''==$token )
        {
            return ['', false];
        }

        return [
            $token,
            $this->pool[Request::Host()]
        ];
    }

    public static function Create(string $token) : bool
    {
        $conn = self::$instance->pool[Request::Host()];
        if( false==$conn )
        {
            return false;
        }
        return $conn->HSet( $token, 'createTime', date('Y-m-d H:i:s') );
    }

    /**
     * @param string $key
     * @param string $val
     * @return bool
     */
    public static function Set( string $key, string $val ) : bool
    {
	    [$token, $conn] = self::$instance->getResource();
        if( ''==$token || false==$conn )
        {
            return false;
        }
        return $conn->HSet( self::GetToken(), $key, $val );
    }

    /**
     * Set : set key information by array for specified session
     * @param array $val
     * @return bool
     */
    public static function MSet( array $val ) : bool
    {
	    [$token, $conn] = self::$instance->getResource();
        if( ''==$token || false==$conn )
        {
            return false;
        }
        return $conn->HmSet( self::GetToken(), $val );
    }

    /**
     * Get : get specified session key information
     * @param string $key
     * @return false|array
     */
    public static function Get( string $key )
    {
	    [$token, $conn] = self::$instance->getResource();
        if( ''==$token || false==$conn )
        {
            return false;
        }
        return $conn->HGet( self::GetToken(), $key );
    }

    /**
     * Del : delete specified session key information
     * @param string $key
     * @return bool
     */
    public static function Del( string $key ) : bool
    {
	    [$token, $conn] = self::$instance->getResource();
        if( ''==$token || false==$conn )
        {
            return false;
        }
        return $conn->HDel( $token, $key );
    }

    /**
     * Info : get all stored session information
     * @return array
     */
    public static function Info() : array
    {
	    [$token, $conn] = self::$instance->getResource();
        if( ''==$token || false==$conn )
        {
            return [];
        }
        return $conn->HGetAll($token);
    }

    /**
     * @return bool
     */
    public static function Destroy() : bool
    {
	    [$token, $conn] = self::$instance->getResource();
        if( ''==$token || false==$conn )
        {
            return false;
        }
        return $conn->Del( $token );
    }

    public static function Exists()
    {
	    [$token, $conn] = self::$instance->getResource();
        if( ''==$token || false==$conn )
        {
            return false;
        }
        return $conn->Exists( $token );
    }

    public static function Has(string $key)
    {
        [$token, $conn] = self::$instance->getResource();
        if( ''==$token || false==$conn )
        {
            return false;
        }
        return $conn->HExists( $token, $key );
    }

    /**
     * @return string
     */
    public static function GetToken() : string
    {
    	$host = Request::Host();
    	$self = self::$instance;
        $tokenFrom = $self->config[$host]['tokenFrom'] ?? '';
        if( ''==$tokenFrom )
        {
            return '';
        }

        $tokenKey  = $self->config[$host]['tokenKey'] ?? self::DEFAULT_TOKEN_KEY;
        return Request::$tokenFrom( $tokenKey );
    }

}