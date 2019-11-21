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
        'handler' => 'RedisSession',
        'host' => '127.0.0.1',
        'port' => 6379,
        'userName' => '',
        'password' => 'louis',
        'timeout' => 3600,
        'prefix'  => 'sess_',
        'cookie' => [
            'expire' => '3600',
            'path' => '/',
            'domain' => '',
            'secure' => false,
            'httponly' => true
        ]
    ];
    /**
     * namespace
     * @var string
     */
    static $namespace = 'ArrowWorker\\Driver\\Session\\';

    public static function Init()
    {
        self::_getSessionId();
    }

    /**
     * init : initialize session driver and get session id(token) from client
     */
    private static function _init()
    {
        if( self::$_isInitialized )
        {
            return self::$_handler;
        }

        $config = Config::Get("Session");
        if ($config )
        {
            self::$_config = array_merge(self::$_config, $config);
        }

        $driver = self::$namespace . self::$_config['handler'];
        self::$_handler = new $driver(
            self::$_config['host'],
            self::$_config['port'],
            self::$_config['userName'],
            self::$_config['password'],
            self::$_config['timeout']
        );

        self::$_isInitialized = true;

        return self::$_handler;
    }

    /**
     * Set : set key information for specified session
     * @param string $key
     * @param string $val
     * @return bool
     */
    public static function Set(string $key, string $val): bool
    {
        return self::_init()->Set(self::$_token[Coroutine::Id()], $key, $val);
    }

    /**
     * Set : set key information by array for specified session
     * @param array $val
     * @return bool
     */
   public static function MultiSet(array $val): bool
    {
        return self::_init()->MSet(self::$_token[Coroutine::Id()], $val);
    }

    /**
     * Get : get specified session key information
     * @param string $key
     * @return false|array
     */
    public static function Get(string $key)
    {
        return self::_init()->Get(self::$_token[Coroutine::Id()], $key);
    }

    /**
     * Del : delete specified session key information
     * @param string $key
     * @return bool
     */
    public static function Del(string $key) : bool
    {
        return self::_init()->Del(self::$_token[Coroutine::Id()], $key);
    }

    /**
     * Id : get or set session id(token)
     * @param string|null $id
     * @return string
     */
    public static function Id(string $id = '') : string
    {
        $coId = Coroutine::Id();
        if( !empty($id) )
        {
            self::$_token[$coId] = $id;
            self::_setSessionCookie();
        }

        return self::$_token[$coId];
    }

    /**
     * Info : get all stored session information
     * @param string $sessionId
     * @return array
     */
    public static function Info(string $sessionId = '') : array
    {
        $handler = self::_init();
        if( !empty($sessionId) )
        {
            return $handler->Info( $sessionId );
        }
        return $handler->Info( self::$_token );
    }

    /**
     * @return bool
     */
    public static function Destroy(): bool
    {
        return self::_init()->Destroy(self::$_token);
    }

    /**
     * _getSessionId : get session id(token) from cookie/get/post data
     */
    private static function _getSessionId()
    {
        $coId  = Coroutine::Id();
        $token = Cookie::Get(self::$_tokenKey);
        if( false!==$token )
        {
            self::$_token[$coId] = $token;
            return ;
        }

        $token = Request::Get(self::$_tokenKey);
        if( ''!==$token )
        {
            self::$_token[$coId] = $token;
            return ;
        }

        $token = Request::Post(self::$_tokenKey);
        if( ''!==$token )
        {
            self::$_token[$coId] = $token;
            return ;
        }

        self::_generateSession();
    }

    /**
     * _setSessionCookie : save session cookies
     * @return bool
     */
    private static function _setSessionCookie() : bool
    {
        return Cookie::Set(self::$_tokenKey,
            self::$_token[Coroutine::Id()],
            self::$_config['cookie']['expire'],
            self::$_config['cookie']['path'],
            self::$_config['cookie']['domain'],
            self::$_config['cookie']['secure'],
            self::$_config['cookie']['httponly']
            );
    }

    /**
     * _generateSession : generate a session id(token)
     */
    private static function _generateSession()
    {
        $coId = Coroutine::Id();
        //session id为自动生成
        if( self::$_token[$coId] != '' )
        {
            return ;
        }

        self::$_token[$coId] = self::$_config['prefix'].crc32( Request::Server('REMOTE_ADDR') . microtime(false) . mt_rand(1,1000000) );
        self::_setSessionCookie();
    }

    public static function Release()
    {
        $coId = Coroutine::Id();

        if( isset(self::$_token[$coId]) )
        {
            unset( self::$_token[$coId] );
        }
    }

}