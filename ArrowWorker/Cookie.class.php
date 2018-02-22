<?php
/**
 * Project ArrowWorker
 * User:   louis
 * Date:   18-1-1
 */

namespace ArrowWorker;


use ArrowWorker\Utilities\Crypto;

class Cookie
{
    public static function Init(array $cookies)
    {
        $_COOKIE = $cookies;
    }

    public static function Get(string $name)
	{
		$name   = static::getEncryptKey($name);
		if( isset($_COOKIE[$name]) )
		{
			return Crypto::Decrypt($_COOKIE[$name]);
		}
		return false;
	}

    public static function Set(string $name, string $val, int $expireSeconds=0, string $path='/',$domain=null)
	{
		$expire = ($expireSeconds==0) ? 0 : time()+$expireSeconds;
		$name   = static::getEncryptKey($name);
		$val    = Crypto::Encrypt($val);
		return setcookie($name, $val, $expire, $path, $domain);
	}

    public static function GetAll()
	{
		return $_COOKIE;
	}

    public static function getEncryptKey(string $name) : string
	{
		$config = Config::App('Cookie');
		if( $config )
		{
			$name = $config['prefix'].$name;
		}
		return md5($name);
	}

}