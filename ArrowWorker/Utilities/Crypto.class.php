<?php

/**
 * Project ArrowWorker
 * User:   louis
 * Date:   18-1-4
 */

namespace ArrowWorker\Utilities;

class Crypto
{
	static $randam = 6;
	static function Encrypt(string $plaintext, string $encryptionFactor) : string
	{
		//$len = strlen($plaintext);
		//$dividePostion = $len+static::$randam;
		return base64_encode($plaintext.$encryptionFactor);
	}

	static function Decrypt(string $ciphertext,  string $encryptionFactor) : string
	{
		$plaintext = base64_decode($ciphertext);
		$realLen = strlen($plaintext) - strlen($encryptionFactor);
		return substr($plaintext,0,$realLen-1);
	}

}