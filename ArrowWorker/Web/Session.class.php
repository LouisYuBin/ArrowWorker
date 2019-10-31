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
    private static $handler;

    /**
     * isInited : sign for judging if session driver is initialized
     * @var bool
     */
    private static $_isInitialized = false;
    /**
     * tokenKey : token key
     * @var string
     */
    static $tokenKey = 'ArrowWorkerSession';
    /**
     * token : current session id(token)
     * @var string
     */
    static $token = '';
    /**
     * config : session config information
     * @var array
     */
    static $config = [
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
        self::getSessionId();
    }

    /**
     * init : initialize session driver and get session id(token) from client
     */
    private static function _init()
    {
        if( self::$_isInitialized )
        {
            return;
        }

        $session = Config::Get("Session");
        if ($session) {
            self::$config = array_merge(self::$config, $session);
        }

        $driver = self::$namespace . self::$config['handler'];
        self::$handler = new $driver(
            self::$config['host'],
            self::$config['port'],
            self::$config['userName'],
            self::$config['password'],
            self::$config['timeout']
        );

        self::$_isInitialized = true;
    }

    /**
     * Set : set key information for specified session
     * @param string $key
     * @param string $val
     * @return bool
     */
    public static function Set(string $key, string $val): bool
    {
        self::_init();
        return self::$handler->Set(self::$token, $key, $val);
    }

    /**
     * Set : set key information by array for specified session
     * @param array $val
     * @return bool
     */
   public static function MultiSet(array $val): bool
    {
        self::_init();
        return self::$handler->MSet(self::$token, $val);
    }

    /**
     * Get : get specified session key information
     * @param string $key
     * @return false|array
     */
    public static function Get(string $key)
    {
        self::_init();
        return self::$handler->Get(self::$token, $key);
    }

    /**
     * Del : delete specified session key information
     * @param string $key
     * @return bool
     */
    public static function Del(string $key) : bool
    {
        self::_init();
        return self::$handler->Del(self::$token, $key);
    }

    /**
     * Id : get or set session id(token)
     * @param string|null $id
     * @return string
     */
    public static function Id(string $id = null): string
    {
        self::_init();
        if( !is_null($id) )
        {
            self::$token = $id;
            self::setSessionCookie();
        }

        return self::$token;
    }

    /**
     * Info : get all stored session information
     * @param string|null $sessionId
     * @return array
     */
    public static function Info(string $sessionId = null) : array
    {
        self::_init();
        if( !is_null($sessionId) )
        {
            return self::$handler->Info( $sessionId );
        }
        return self::$handler->Info( self::$token );
    }

    /**
     * Destory : destory a session
     * @return bool
     */
    public static function Destory(): bool
    {
        self::_init();
        return self::$handler->Destory(self::$token);
    }

    /**
     * getSessionId : get session id(token) from cookie/get/post data
     */
    private static function getSessionId()
    {
        $coId  = Coroutine::Id();
        $token = Cookie::Get(self::$tokenKey);
        if( false!==$token )
        {
            self::$token[$coId] = $token;
            return ;
        }

        $token = Request::Get(self::$tokenKey);
        if( ''!==$token )
        {
            self::$token[$coId] = $token;
            return ;
        }

        $token = Request::Post(self::$tokenKey);
        if( false!==$token )
        {
            self::$token[$coId] = $token;
            return ;
        }

        static::generateSession();
    }

    /**
     * setSessionCookie : save session cookies
     * @return bool
     */
    private static function setSessionCookie() : bool
    {
        return Cookie::Set(self::$tokenKey,
            self::$token[Coroutine::Id()],
            self::$config['cookie']['expire'],
            self::$config['cookie']['path'],
            self::$config['cookie']['domain'],
            self::$config['cookie']['secure'],
            self::$config['cookie']['httponly']
            );
    }

    /**
     * generateSession : generate a session id(token)
     */
    static function generateSession()
    {
        $coId = Coroutine::Id();
        //session id为自动生成
        if( self::$token[$coId] != '' )
        {
            return ;
        }

        self::$token[$coId] = self::$config['prefix'].crc32( Request::Server('REMOTE_ADDR') . microtime(false) . mt_rand(1,1000000) );
        self::setSessionCookie();
    }

    public static function Release()
    {
        $coId = Coroutine::Id();

        if( isset(self::$token[$coId]) )
        {
            unset( self::$token[$coId] );
        }
    }

}