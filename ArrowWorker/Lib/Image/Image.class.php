<?php
/**
 * User: louis
 * Time: 18-5-24 下午6:33
 */

namespace ArrowWorker\Lib\Image;

class Image
{
    /**
     * @param string $file
     * @return bool|Gd|ImageMagick
     * @throws \Exception
     */
    public static function Open(string $file)
    {
        if( extension_loaded('imagick') )
        {
            return ImageMagick::Open($file);
        }
        if( extension_loaded('gd') )
        {
            return Gd::Open($file);
        }
        return false;
    }

    /**
     * @param string $file
     * @return bool|Gd|ImageMagick
     * @throws \Exception
     */
    public static function Create(int $width, int $height, array $bg=[255,255,255,1], string $type='GIF')
    {
        if( extension_loaded('imagick') )
        {
            return ImageMagick::Create($width,$height,$bg,$type);
        }
        if( extension_loaded('gd') )
        {
            return Gd::Create($width,$height,$bg,$type);
        }
        return false;
    }

}