<?php
/**
 * Project ArrowWorker
 * User:   louis
 * Date:   17-12-31
 */

namespace ArrowWorker\Web;

use ArrowWorker\Component\Cache\Pool;
use ArrowWorker\Library\Coroutine;
use ArrowWorker\Config;
use ArrowWorker\Log;


/**
 * Class Session
 * @package ArrowWorker
 */
class Session
{

    /**
     *
     */
    const CONFIG_NAME = 'Session';

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
        $configs = Config::Get( self::CONFIG_NAME );
        if ( !is_array( $configs ) )
        {
            Log::Dump( '[ Session ] initialize config failed' );
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
                Log::Dump( "[ Session ] {$serverNames} config incorrect : " . json_encode( $config ) );
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


    private static function _getConn()
    {
        return Pool::GetConnection( Request::Host() );
    }

    /**
     * @param string $key
     * @param string $val
     * @return bool
     */
    public static function Set( string $key, string $val ) : bool
    {
        $token = self::GetToken();
        if( ''==$token )
        {
            return false;
        }

        $conn = self::_getConn();
        if( false==$conn )
        {
            return false;
        }
        return $conn->Set( self::GetToken(), $key, $val );
    }

    /**
     * Set : set key information by array for specified session
     * @param array $val
     * @return bool
     */
    public static function MultiSet( array $val ) : bool
    {
        $token = self::GetToken();
        if( ''==$token )
        {
            return false;
        }

        $conn = self::_getConn();
        if( false==$conn )
        {
            return false;
        }
        return $conn->MSet( self::GetToken(), $val );
    }

    /**
     * Get : get specified session key information
     * @param string $key
     * @return false|array
     */
    public static function Get( string $key )
    {
        $token = self::GetToken();
        if( ''==$token )
        {
            return false;
        }

        $conn = self::_getConn();
        if( false==$conn )
        {
            return false;
        }
        return $conn->Get( self::GetToken(), $key );
    }

    /**
     * Del : delete specified session key information
     * @param string $key
     * @return bool
     */
    public static function Del( string $key ) : bool
    {
        $token = self::GetToken();
        if( ''==$token )
        {
            return false;
        }

        $conn = self::_getConn();
        if( false==$conn )
        {
            return false;
        }
        return $conn->Del( $token, $key );
    }

    /**
     * Info : get all stored session information
     * @return array
     */
    public static function Info() : array
    {
        $token = self::GetToken();
        if( ''==$token )
        {
            return [];
        }

        $conn = self::_getConn();
        if( false==$conn )
        {
            return [];
        }
        return $conn->Info( self::GetToken() );
    }

    /**
     * @return bool
     */
    public static function Destroy() : bool
    {
        $token = self::GetToken();
        if( ''==$token )
        {
            return false;
        }

        $conn = self::_getConn();
        if( false==$conn )
        {
            return '';
        }
        return $conn->Destroy( $token );
    }

    /**
     * GetToken : get session id(token) from cookie/get/post data
     */
    public static function GetToken() : string
    {
        if( !isset(self::$_config[Request::Host()]['tokenFrom']) )
        {
            return '';
        }

        $tokenKey  = self::$_config[Request::Host()]['tokenKey'];
        $tokenFrom = self::$_config[Request::Host()]['tokenFrom'];
        return 'Cookie'==$tokenFrom ? Cookie::Get( $tokenKey ) : Request::$tokenFrom( $tokenKey );
    }

}