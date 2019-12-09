<?php

/**
 * Project ArrowWorker
 * User:   louis
 * Date:   18-1-4
 */

namespace ArrowWorker\Library\Crypto;

use ArrowWorker\Config;

class CryptoArrow
{
	static $factor = "";
	static $defaultFactor = "Louis";

	static function Init(string $factor="")
    {
        if( !empty($factor))
        {
            static::$factor = $factor;
            return ;
        }

	    if( !empty(static::$factor) )
        {
            return ;
        }

        $config = Config::Get("Cryto");
        if( !$config )
        {
            static::$factor = static::$defaultFactor;
            return ;
        }

        if( !isset($config['factor']) )
        {
            static::$factor = static::$defaultFactor;
            return ;
        }
        static::$factor = $config['factor'];

    }

	static function Encrypt(string $plaintext, string $factor="") : string
	{
	    static::Init($factor);
        $plaintext = $plaintext ^ static::$factor;
		return base64_encode($plaintext);
	}

	static function Decrypt(string $ciphertext,  string $factor="") : string
	{
        static::Init($factor);
        $plaintext = base64_decode($ciphertext);
		if( $plaintext )
        {
            return $plaintext ^ static::$factor;
        }
        return false;
	}

}