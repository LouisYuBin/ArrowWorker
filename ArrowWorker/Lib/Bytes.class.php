<?php
/**
 * By yubin at 2019/2/26 9:33 AM.
 */

class Byte
{

    /**
     * convert string to byte array
     * @param string $string
     * @param bool $isToHex
     * @return array
     */
    public static function StringToBytes(string $string, bool $isToHex=false) : array
    {
        $bytes  = [];
        $strLen = strlen($string);
        for($i=0; $i<$strLen; $i++)
        {
            if( $isToHex )
            {
                $bytes[] = dechex(ord($string[$i]));
                continue ;
            }
            $bytes[] = ord($string[$i]);
        }
        return $bytes;
    }

    /**
     * convert byte array to string
     * @param array  $bytes
     * @param bool  $isFromHex
     * @return string
     */
    public static function BytesToString(array $bytes, bool $isFromHex=false) : string
    {
        if( $isFromHex )
        {
            $bytes  = array_map('dechex', $bytes);
        }
        $bytes  = array_map('chr', $bytes);
        $string = implode('', $bytes);
        return $string;
    }

}