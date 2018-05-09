<?php
/**
 * Project ArrowWorker
 * User:   louis
 * Date:   17-12-31
 */

namespace ArrowWorker;


class Session
{
    static $handler;

    static $isInited = false;
    static $tokenKey = 'ArrowWorkerSession';
    static $token = '';
    static $config = [
        'handler' => 'files',
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
    static $namespace = 'ArrowWorker\\Driver\\Session\\';

    static function init()
    {
        static::getSessionId();

        if (static::$isInited) {
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

    static function Set(string $key, string $val): bool
    {
        static::init();
        return static::$handler->Set(static::$token, $key, $val);
    }

    static function Get(string $key)
    {
        static::init();
        return static::$handler->Get(static::$token, $key);
    }

    static function Del(string $key) : bool
    {
        static::init();
        return static::$handler->Del(static::$token, $key);
    }

    static function Id(string $id = null): string
    {
        static::init();
        if( !is_null($id) )
        {
            static::$token = $id;
            static::setSessionCookie();
        }

        return static::$token;
    }


    static function Destory(): bool
    {
        static::init();
        static::$handler->Destory(static::$token);
    }

    static function getSessionId()
    {
        static::$token = '';
        $token = Cookie::Get(static::$tokenKey);
        var_dump($token);
        if (false !== $token) {
            static::$token = $token;
            return ;
        }

        $token = Request::Get(static::$tokenKey);
        var_dump($token);
        if (false !== $token) {
            static::$token = $token;
            return ;
        }

        $token = Request::Post(static::$tokenKey);
        var_dump($token);
        if (false !== $token) {
            static::$token = $token;
            return ;
        }

        var_dump($token);
        static::generateSession();
    }

    static function setSessionCookie() : bool
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

    static function generateSession()
    {
        //session id为自动生成
        if( static::$token != '' )
        {
            return ;
        }

        $remoteAddr = APP_TYPE==='swWeb' ?
            Request::Server('remote_addr') :
            Request::Server('REMOTE_ADDR');

        static::$token = static::$config['prefix'].crc32( $remoteAddr . microtime(false) . mt_rand(1,1000000) );
        static::setSessionCookie();
    }

}