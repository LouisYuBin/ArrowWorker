<?php
/**
 * Project ArrowWorker
 * User:   louis
 * Date:   17-12-31
 */

namespace ArrowWorker;


class Session
{
	static $isInited = false;
	static $swSessionCookie = "swooleSessionCookie";
	static $config = [
		'handler'  => 'files',
		'savePath' => '/tmp',
		'host'	   => '127.0.0.1',
		'port'	   => 6379,
		'password' => 'louis',
		'timeout'  => 3600,
		'cookie'   => [
			'lifetime' => '3600',
			'path'     => '/',
			'domain'   => '',
			'secure'   => 'false',
			'httponly' => 'true'
			]
	];
	static $driverPath = 'ArrowWorker\\Driver\\Session\\';

	static function init()
	{
		if( static::$isInited )
		{
			return;
		}

		$session = Config::App("Session");
		if( $session )
		{
			static::$config = array_merge( static::$config, $session);
		}

		if( static::$config['handler'] != 'files' )
		{
			$driver  = static::$driverPath.static::$config['handler'];
			$handler = new $driver(static::$config['host'], static::$config['port'], static::$config['password'], static::$config['timeout']);
			if( !session_set_save_handler($handler,true) )
			{
				throw new \Exception('session_set_save_handler failed',500);
			}
		}
		else
		{
			ini_set('session.save_handler', static::$config['handler']);
			ini_set('session.save_path', static::$config['savePath']);
		}
		session_start(['cookie_lifetime' => 86400]);
		session_set_cookie_params(static::$config['cookie']['lifetime'], static::$config['cookie']['path'], static::$config['cookie']['domain'], static::$config['cookie']['secure'], static::$config['cookie']['httponly']);
        static::setSessionCookie();
		static::$isInited = true;
	}

	static function Set(string $key, string $val)
	{
		static::init();
		$_SESSION[$key] = $val;
	}

	static function Get(string $key)
	{
		static::init();
		return isset($_SESSION[$key]) ? $_SESSION[$key] : false;
	}

	static function Del(string $key)
	{
		static::init();
		if (isset($_SESSION[$key]))
		{
			unset($_SESSION[$key]);
			return true;
		}
		return false;
	}

	static function Id()
	{
		static::init();
		return session_id();
	}


	static function Destory()
	{
		static::init();
		session_destroy();
	}

	static function setSessionCookie()
    {
        if( APP_TYPE != 'swWeb' )
        {
            return ;
        }

        $isOk = Cookie::Set( static::$swSessionCookie, static::Id() );
        if( !$isOk )
        {
            throw new \Exception("set session cookie error",500);
        }
    }

}