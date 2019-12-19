<?php
/**
 * Project ArrowWorker
 * User:   louis
 * Date:   17-12-31
 */

namespace ArrowWorker\Web;

use ArrowWorker\Component\Cache\Pool;
use ArrowWorker\Config;
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
    private static $_config = [];

    public static function Init()
    {
        self::_initConfig();
        self::_initPool();
    }

    private static function _initPool()
    {
        foreach ( self::$_config as $host => $config )
        {
            $config[ 'driver' ] = 'Redis';
            Pool::Init( [ $host => $config[ 'poolSize' ] ], [ $host => $config ] );
        }
    }

    private static function _initConfig()
    {
        $configs = Config::Get( self::MODULE_NAME );
        if ( !is_array( $configs ) )
        {
            Log::Dump( 'initialize config failed', Log::TYPE_WARNING, self::MODULE_NAME );
            return;
        }
        self::$_config = self::_parseConfig( $configs );
    }

    /**
     * @param array $configs
     * @return array
     */
    private static function _parseConfig( array $configs ) : array
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


    private static function _getResource()
    {
        $token = self::GetToken();
        if( ''==$token )
        {
            return ['', false];
        }

        return [
            $token,
            Pool::GetConnection( Request::Host() )
        ];
    }

    public static function Create(string $token) : bool
    {
        $conn = Pool::GetConnection( Request::Host() );
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
        [$token, $conn] = self::_getResource();
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
        [$token, $conn] = self::_getResource();
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
        [$token, $conn] = self::_getResource();
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
        [$token, $conn] = self::_getResource();
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
        [$token, $conn] = self::_getResource();
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
        [$token, $conn] = self::_getResource();
        if( ''==$token || false==$conn )
        {
            return false;
        }
        return $conn->Del( $token );
    }

    public static function Exists()
    {
        [$token, $conn] = self::_getResource();
        if( ''==$token || false==$conn )
        {
            return false;
        }
        return $conn->Exists( $token );
    }

    public static function Has(string $key)
    {
        [$token, $conn] = self::_getResource();
        if( ''==$token || false==$conn )
        {
            return false;
        }
        return $conn->HExists( $token, $key );
    }

    /**
     * GetToken : get session id(token) from cookie/get/post data
     */
    public static function GetToken() : string
    {
        $tokenFrom = self::$_config[Request::Host()]['tokenFrom'] ?? '';
        if( ''==$tokenFrom )
        {
            return '';
        }

        $tokenKey  = self::$_config[Request::Host()]['tokenKey'] ?? self::DEFAULT_TOKEN_KEY;
        return Request::$tokenFrom( $tokenKey );
    }

}