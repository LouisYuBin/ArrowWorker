<?php
/**
 * Project ArrowWorker
 * User:   louis
 * Date:   17-12-31
 */

namespace ArrowWorker;
use ArrowWorker\Driver\Session\MemcachedSession;
use ArrowWorker\Driver\Session\RedisSession;


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
    static $handler;

    /**
     * isInited : sign for judging if session driver is initialized
     * @var bool
     */
    static $isInited = false;
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

    public static function Reset()
    {
        static::$token = '';
        static::getSessionId();
    }

    /**
     * init : initialize session driver and get session id(token) from client
     */
    private static function init()
    {
        if( static::$isInited )
        {
            return;
        }

        $session = Config::App("Session");
        if ($session) {
            static::$config = array_merge(static::$config, $session);
        }

        $driver = static::$namespace . static::$config['handler'];
        static::$handler = new $driver(
            static::$config['host'],
            static::$config['port'],
            static::$config['userName'],
            static::$config['password'],
            static::$config['timeout']
        );

        static::$isInited = true;
    }

    /**
     * Set : set key information for specified session
     * @param string $key
     * @param string $val
     * @return bool
     */
    public static function Set(string $key, string $val): bool
    {
        static::init();
        return static::$handler->Set(static::$token, $key, $val);
    }

    /**
     * Set : set key information by array for specified session
     * @param array $val
     * @return bool
     */
   public static function MultiSet(array $val): bool
    {
        static::init();
        return static::$handler->MSet(static::$token, $val);
    }

    /**
     * Get : get specified session key information
     * @param string $key
     * @return false|array
     */
    public static function Get(string $key)
    {
        static::init();
        return static::$handler->Get(static::$token, $key);
    }

    /**
     * Del : delete specified session key information
     * @param string $key
     * @return bool
     */
    public static function Del(string $key) : bool
    {
        static::init();
        return static::$handler->Del(static::$token, $key);
    }

    /**
     * Id : get or set session id(token)
     * @param string|null $id
     * @return string
     */
    public static function Id(string $id = null): string
    {
        static::init();
        if( !is_null($id) )
        {
            static::$token = $id;
            static::setSessionCookie();
        }

        return static::$token;
    }

    /**
     * Info : get all stored session information
     * @param string|null $sessionId
     * @return array
     */
    public static function Info(string $sessionId = null) : array
    {
        static::init();
        if( !is_null($sessionId) )
        {
            return static::$handler->Info( $sessionId );
        }
        return static::$handler->Info( static::$token );
    }

    /**
     * Destory : destory a session
     * @return bool
     */
    public static function Destory(): bool
    {
        static::init();
        return static::$handler->Destory(static::$token);
    }

    /**
     * getSessionId : get session id(token) from cookie/get/post data
     */
    private static function getSessionId()
    {
        $token = Cookie::Get(static::$tokenKey);
        if( false!==$token )
        {
            static::$token = $token;
            return ;
        }

        $token = Request::Get(static::$tokenKey);
        if( false!==$token )
        {
            static::$token = $token;
            return ;
        }

        $token = Request::Post(static::$tokenKey);
        if( false!==$token )
        {
            static::$token = $token;
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
        return Cookie::Set(static::$tokenKey,
            static::$token,
            static::$config['cookie']['expire'],
            static::$config['cookie']['path'],
            static::$config['cookie']['domain'],
            static::$config['cookie']['secure'],
            static::$config['cookie']['httponly']
            );
    }

    /**
     * generateSession : generate a session id(token)
     */
    static function generateSession()
    {
        //session id为自动生成
        if( static::$token != '' )
        {
            return ;
        }

        static::$token = static::$config['prefix'].crc32( Request::Server('REMOTE_ADDR') . microtime(false) . mt_rand(1,1000000) );
        static::setSessionCookie();
    }

}