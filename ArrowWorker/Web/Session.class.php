<?php
/**
 * Project ArrowWorker
 * User:   louis
 * Date:   17-12-31
 */

namespace ArrowWorker\Web;

use ArrowWorker\Lib\Coroutine;
use ArrowWorker\Driver\Session\MemcachedSession;
use ArrowWorker\Driver\Session\RedisSession;
use ArrowWorker\Config;


/**
 * Class Session
 * @package ArrowWorker
 */
class Session
{
    /**
     * handler : driver handler
     * @var MemcachedSession|RedisSession
     */
    private static $_handler;

    /**
     * isInited : sign for judging if session driver is initialized
     * @var bool
     */
    private static $_isInitialized = false;

    /**
     * tokenKey : token key
     * @var string
     */
    private static $_tokenKey = 'ArrowWorkerSession';

    /**
     * token : current session id(token)
     * @var array
     */
    private static $_token = [];

    /**
     * config : session config information
     * @var array
     */
    private static $_config = [
        'handler'  => 'RedisSession',
        'host'     => '127.0.0.1',
        'port'     => 6379,
        'userName' => '',
        'password' => 'louis',
        'timeout'  => 3600,
        'prefix'   => 'sess_',
        'cookie'   => [
            'expire'   => '3600',
            'path'     => '/',
            'domain'   => '',
            'secure'   => false,
            'httponly' => true,
        ],
    ];

    /**
     * namespace
     * @var string
     */
    static $namespace = 'ArrowWorker\\Driver\\Session\\';

    public static function Init()
    {
        // todo
    }

    private static function _init()
    {
        if ( self::$_isInitialized )
        {
            return self::$_handler;
        }

        $config = Config::Get( "Session" );
        if ( $config )
        {
            self::$_config = array_merge( self::$_config, $config );
        }

        $driver         = self::$namespace . self::$_config[ 'handler' ];
        self::$_handler = new $driver(
            self::$_config[ 'host' ],
            self::$_config[ 'port' ],
            self::$_config[ 'userName' ],
            self::$_config[ 'password' ],
            self::$_config[ 'timeout' ]
        );

        self::$_isInitialized = true;

        return self::$_handler;
    }

    /**
     * @param string $key
     * @param string $val
     * @return bool
     */
    public static function Set( string $key, string $val ) : bool
    {
        return self::_init()->Set( self::GetToken(), $key, $val );
    }

    /**
     * Set : set key information by array for specified session
     * @param array $val
     * @return bool
     */
    public static function MultiSet( array $val ) : bool
    {
        return self::_init()->MSet( self::GetToken(), $val );
    }

    /**
     * Get : get specified session key information
     * @param string $key
     * @return false|array
     */
    public static function Get( string $key )
    {
        return self::_init()->Get( self::GetToken(), $key );
    }

    /**
     * Del : delete specified session key information
     * @param string $key
     * @return bool
     */
    public static function Del( string $key ) : bool
    {
        return self::_init()->Del( self::GetToken(), $key );
    }

    /**
     * Info : get all stored session information
     * @return array
     */
    public static function Info() : array
    {
        $handler = self::_init();
        $token   = self::GetToken();
        return $handler->Info( $token );
    }

    /**
     * @return bool
     */
    public static function Destroy() : bool
    {
        return self::_init()->Destroy( self::GetToken() );
    }

    /**
     * GetToken : get session id(token) from cookie/get/post data
     */
    public static function GetToken() : string
    {
        $token = Cookie::Get( self::$_tokenKey );
        if ( '' !== $token )
        {
            return $token;
        }

        $token = Request::Get( self::$_tokenKey );
        if ( '' !== $token )
        {
            return $token;
        }

        $token = Request::Post( self::$_tokenKey );
        if ( '' !== $token )
        {
            return $token;
        }

        return '';

    }

    /**
     * _setSessionCookie : save session cookies
     * @return bool
     */
    private static function _setSessionCookie() : bool
    {
        return Cookie::Set( self::$_tokenKey,
            self::$_token[ Coroutine::Id() ],
            self::$_config[ 'cookie' ][ 'expire' ],
            self::$_config[ 'cookie' ][ 'path' ],
            self::$_config[ 'cookie' ][ 'domain' ],
            self::$_config[ 'cookie' ][ 'secure' ],
            self::$_config[ 'cookie' ][ 'httponly' ]
        );
    }

    /**
     * _generateToken : generate a session id(token)
     */
    private static function _generateToken()
    {
        $coId = Coroutine::Id();
        if ( self::$_token[ $coId ] != '' )
        {
            return;
        }

        self::$_token[ $coId ] = self::$_config[ 'prefix' ] .
                                 crc32( Request::Server( 'REMOTE_ADDR' ) . microtime( false ) . mt_rand( 1, 1000000 ) );
        self::_setSessionCookie();
    }

    public static function Release()
    {
        $coId = Coroutine::Id();

        if ( isset( self::$_token[ $coId ] ) )
        {
            unset( self::$_token[ $coId ] );
        }
    }

}