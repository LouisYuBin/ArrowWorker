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
        else if( extension_loaded('gd') )
        {
            return Gd::Open($file);
        }
        return false;
    }

}