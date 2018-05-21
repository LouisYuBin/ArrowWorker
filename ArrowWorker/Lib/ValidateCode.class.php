<?php
namespace ArrowWorker\Lib;
use ArrowWorker\Config;
use ArrowWorker\Response;

/**
 * Class ValidateCode
 */
class ValidationCode
{
    /**
     * code factor
     * @var string
     */
    private $codeFactor = 'abcdefghkmnprstuvwxyzABCDEFGHKMNPRSTUVWXYZ23456789';//随机因子

    /**
     * code : validation code
     * @var
     */
    private $code;
    /**
     * codeLen : code length
     * @var int
     */
    private $codeLen = 6;

    /**
     * width : validation code image with
     * @var int
     */
    private $width = 130;

    /**
     * height :  : validation code image height
     * @var int
     */
    private $height = 50;

    /**
     * img : image resource handler
     * @var
     */
    private $img;//图形资源句柄

    /**
     * font : code font
     * @var string
     */
    private $font;//指定的字体

    /**
     * fontSize : code font size
     * @var int
     */
    private $fontSize = 25;

    /**
     * fontColor : code font color
     * @var
     */
    private $fontColor;//指定字体颜色

    /**
     * handler
     * @var null|self
     */
    private static $handler;

    /**
     * ValidateCode constructor.
     */
    private function __construct()
    {
        $config = Config::App('ValidationCode');
        if( !$config )
        {
            return ;
        }
        $this->font = dirname(__FILE__).'/ZEBRRA__.ttf';//注意字体路径要写对，否则显示不了图片
    }

    /**
     * init : init the ValidateCode object
     * @return self
     */
    private static function init()
    {
        if( !static::$handler )
        {
            static::$handler = new self();
        }
        return static::$handler;
    }

    /**
     * generateCode : generate validation code
     */
    private function generateCode()
    {
        $len = strlen($this->codeFactor)-1;
        for( $i=0; $i<$this->codeLen; $i++ )
        {
            $this->code .= $this->codeFactor[ mt_rand(0,$len) ];
        }
    }

    /**
     * createBg : create background image
     * @return bool
     */
    private function createBg() : bool
    {
        $this->img = imagecreatetruecolor( $this->width, $this->height );
        $color = imagecolorallocate(
            $this->img,
            mt_rand(157,255),
            mt_rand(157,255),
            mt_rand(157,255)
        );

        return imagefilledrectangle(
            $this->img,
            0,
            $this->height,
            $this->width,
            0,
            $color
        );
    }

    /**
     * writeCode : write validation code to image
     */
    private function writeCode()
    {
        $x = $this->width / $this->codeLen;
        for( $i=0; $i<$this->codeLen; $i++)
        {
            $this->fontColor = imagecolorallocate(
                $this->img,
                mt_rand(0,156),
                mt_rand(0,156),
                mt_rand(0,156)
            );

            imagettftext(
                $this->img,
                $this->fontSize,
                mt_rand(-30,30),
                $x*$i+mt_rand(1,5),
                $this->height / 1.4,
                $this->fontColor,
                $this->font,
                $this->code[$i]
            );
        }
    }

    /**
     * createLine ： create interference factor( snowflake and line )
     */
    private function createLine()
    {
        $result = false;
        for( $i=0; $i<6; $i++ )
        {
            $color = imagecolorallocate(
                $this->img,
                mt_rand(0,156),
                mt_rand(0,156),
                mt_rand(0,156)
            );

            $result = imageline(
                $this->img,
                mt_rand(0,$this->width),
                mt_rand(0,$this->height),
                mt_rand(0,$this->width),
                mt_rand(0,$this->height),
                $color
            );

            if( !$result )
            {
                throw new \Exception("call imageline error", 500);
            }
        }

        //snowflake
        for( $i=0; $i<100; $i++ )
        {
            $color = imagecolorallocate(
                $this->img,
                mt_rand(200,255),
                mt_rand(200,255),
                mt_rand(200,255)
            );

            $result = imagestring(
                $this->img,
                mt_rand(1,5),
                mt_rand(0,$this->width),
                mt_rand(0,$this->height),
                '*',
                $color
            );

            if( !$result )
            {
                throw new \Exception("call imagestring error", 500);
            }
        }
        return $result;
    }

    /**
     * output : output image
     * @return bool|string
     */
    private function output()
    {
        Response::Header("Content-type",'image/png');
        if( !imagepng($this->img) )
        {
            return false;
        }

        if( !imagedestroy($this->img) )
        {
            return false;
        }

        return strtolower($this->code);
    }

    /**
     * Generate : generate code and image
     * @return bool|string
     */
    public static function Create()
    {
        $handler = static::init();
        if( !$handler->createBg() )
        {
            return false;
        }

        if( !$handler->createLine() )
        {
            return false;
        }

        $handler->generateCode();
        $handler->writeCode();
        return $handler->output();
    }

}
