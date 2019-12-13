<?php
/**
 * By yubin at 2019/2/18 4:02 PM.
 */

namespace ArrowWorker\Library;

class Bytes
{

    /**
     * convert string to byte array
     * @param string $string
     * @param bool   $isToHex
     * @return array
     */
    public static function StringToBytes(string $string, bool $isToHex = false): array
    {
        $bytes = [];
        $strLen = strlen($string);
        for ($i = 0; $i < $strLen; $i++)
        {
            if ($isToHex)
            {
                $bytes[] = dechex(ord($string[$i]));
                continue;
            }
            $bytes[] = ord($string[$i]);
        }
        return $bytes;
    }
    /**
     * convert byte array to string
     * @param array $bytes
     * @param bool  $isFromHex
     * @return string
     */
    public static function BytesToString(array $bytes, bool $isFromHex = false): string
    {
        if ($isFromHex)
        {
            $bytes = array_map('hexdec', $bytes);
        }
        $bytes = array_map('chr', $bytes);
        $string = implode('', $bytes);
        return $string;
    }

    /**
     * @param int $val
     * @return array
     */
    public static function IntegerToBytes(int $val): array
    {
        $bytes = [];
        $bytes[3] = ($val & 0xff);
        $bytes[2] = ($val >> 8 & 0xff);
        $bytes[1] = ($val >> 16 & 0xff);
        $bytes[0] = ($val >> 24 & 0xff);
        return $bytes;
    }

    /**
     * @param int $short
     * @return array
     */
    public static function ShortToBytes(int $short) : array
    {
        $bytes = array();
        $bytes[1] = ($short & 0xff);
        $bytes[0] = ($short >> 8 & 0xff);
        return $bytes;
    }

    /**
     * @param array $bytes
     * @return int
     */
    public static function BytesToShort(array $bytes): int
    {
        $val = 0;
        $val = $bytes[1] & 0xff;
        $val = $val << 8;
        $val |= $bytes[0] & 0xff;
        return $val;
    }

    public static function BytesToInteger(array $bytes)
    {
        $val = 0;
        $val = $bytes[0] & 0xff;
        $val <<= 8;
        $val |= $bytes[1] & 0xff;
        $val <<= 8;
        $val |= $bytes[2] & 0xff;
        $val <<= 8;
        $val |= $bytes[3] & 0xff;
        return $val;
    }

}
