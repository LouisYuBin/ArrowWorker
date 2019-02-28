<?php
/**
 * By yubin at 2019/2/26 9:33 AM.
 */

class Byte
{

    /**
     * convert string to byte array
     * @param string $string
     * @param string $charset
     * @return array
     */
    public static function StringToBytes(string $string, string $charset='UTF-8') : array
     {
        $string = iconv($charset,'UTF-16', $string);
        $bytes  = [];
        $strLen = strlen($string);
        for($i=0; $i<$strLen; $i++)
        {
            $bytes[] = dechex(ord($string[$i]));
        }
        return $bytes;

    }

    /**
     * convert byte array to string
     * @param array  $bytes
     * @param string $charset
     * @return string
     */
    public static function BytesToString(array $bytes, string $charset='UTF-8') : string
    {
        $bytes  = array_map('chr', $bytes);
        $string = implode('', $bytes);
        $string = iconv('UTF-16', $charset, $string);
        return $string;
    }

}