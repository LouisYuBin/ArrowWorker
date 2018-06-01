<?php
/**
 * User: louis
 * Time: 18-5-24 下午12:53
 */

namespace ArrowWorker\Lib\Image;

use ArrowWorker\Lib\Image\Gif\GifHelper;


/**
 * Class Gd
 * @package Image
 */
class Gd
{
    /**
     * Top left of the background-image.
     */
    const TOP_LEFT = 'top-left';
    /**
     * Top center of the background-image.
     */
    const TOP_CENTER = 'top-center';
    /**
     * Top right of the background-image.
     */
    const TOP_RIGHT = 'top-right';
    /**
     * Center left of the background-image.
     */
    const CENTER_LEFT = 'center-left';
    /**
     * Center of the background-image.
     */
    const CENTER = 'center';
    /**
     * Center right of the background-image.
     */
    const CENTER_RIGHT = 'center-right';
    /**
     * Center left of the background-image.
     */
    const BOTTOM_LEFT = 'bottom-left';
    /**
     * Bottom center of the background-image.
     */
    const BOTTOM_CENTER = 'bottom-center';
    /**
     * Bottom right of the background-image.
     */
    const BOTTOM_RIGHT = 'bottom-right';
    /*
     * font path
     * */
    const FONT_PATh = APP_PATH.DIRECTORY_SEPARATOR.APP_RUNTIME_DIR.'/Font/';
    /**
     * image object
     */
    private $img;
    /**
     * image file path.
     */
    private $file   = '';
    /**
     * image file width.
     */
    private $width  = 0;
    /**
     * image file height
     */
    private $height = 0;
    /**
     * image file type
     */
    private $type   = 0;
    /**
     * image file block
     */
    private $blocks = '';
    /**
     * is image file a gif
     */
    private $animated = false;

    /**
     * Gd constructor.
     * @param $img
     * @param string $imageFile
     * @param int $width
     * @param int $height
     * @param int $type
     */
    private function __construct($img, string $imageFile, int $width, int $height, int $type, $blocks = '', bool $animated = false )
    {
        $this->img    = $img;
        $this->file   = $imageFile;
        $this->width  = $width;
        $this->height = $height;
        $this->type   = $type;
        $this->blocks   = $blocks;
        $this->animated = $animated;
    }

    /**
     * GetImgInfo
     * @param string $imgPath
     * @throws \Exception
     * @return int
     */
    public static function getImgInfo(string $imgPath)
    {
        $info = getimagesize($imgPath);
        if( false==$info )
        {
            throw new \Exception("getimagesize ({$imgPath}) error.");
        }
        return $info[2];
    }

    /**
     * Open
     * @param string $imageFile
     * @return Gd
     */
    public static function Open(string $imageFile) : self
    {
        if( !file_exists($imageFile) )
        {
            throw new \Exception("image file : {$imageFile} does not exists");
        }
        switch( static::getImgInfo($imageFile) )
        {
            case IMAGETYPE_GIF :
                return static::createGif( $imageFile );
            case IMAGETYPE_JPEG :
                return static::createJpeg( $imageFile );
            case IMAGETYPE_PNG :
                return static::createPng( $imageFile );
            case IMAGETYPE_WBMP :
                return static::createWbmp( $imageFile );

        }
        throw new \Exception('Could not open '.$imageFile.'. File type not supported.');
    }

    /**
     * Load a JPEG image.
     *
     * @param string $imageFile File path to image.
     *
     * @return Gd
     * @throws \Exception
     */
    private static function createJpeg( string $imageFile ){
        $img = @imagecreatefromjpeg( $imageFile );

        if( !$img ){
            throw new \Exception( 'Could not open '.$imageFile.' Not a valid '.IMAGETYPE_JPEG.' file.' );
        }

        return new self( $img, $imageFile, imagesx( $img ), imagesy( $img ), IMAGETYPE_JPEG );
    }

    /**
     * Load a PNG image.
     *
     * @param string $imageFile File path to image.
     *
     * @return Gd
     * @throws \Exception
     */
    private static function createPng( string $imageFile )
    {
        $img = @imagecreatefrompng( $imageFile );

        if( !$img )
        {
            throw new \Exception( 'Could not open '.$imageFile.'. Not a valid PNG file.' );
        }

        $gd = new self( $img, $imageFile, imagesx( $img ), imagesy( $img ), IMAGETYPE_PNG);
        static::alphaSetting($img, true);
        return $gd;
    }

    /**
     * alphaSetting
     * @param $img
     * @param bool $flag
     * @param array $color
     * @param int $alpha
     */
    private static function alphaSetting($img, bool $flag, array $color=[255,255,255], int $alpha=127)
    {
        $newtransparentcolor = imagecolorallocatealpha(
            $img,
            $color[0],
            $color[1],
            $color[2],
            $alpha
        );
        imagefill( $img, 0, 0, $newtransparentcolor );
        imagecolortransparent( $img, $newtransparentcolor );
        imagealphablending( $img, false );
        imagesavealpha( $img, $flag );
    }



    /**
     * Load a WBMP image.
     *
     * @param string $imageFile
     *
     * @return Gd
     * @throws \Exception
     */
    private static function createWbmp( string $imageFile )
    {
        $img = @imagecreatefromwbmp( $imageFile );

        if( !$img )
        {
            throw new \Exception( 'Could not open '.$imageFile.' Not a valid WEBMP file.' );
        }

        return new self( $img, $imageFile, imagesx( $img ), imagesy( $img ), IMAGETYPE_WBMP );
    }

    /**
     * Load a GIF image.
     *
     * @param string $imageFile
     *
     * @return Gd
     * @throws \Exception
     */
    private static function createGif( string $imageFile )
    {
        $gift     = new GifHelper();
        $bytes    = $gift->open($imageFile);
        $animated = $gift->isAnimated($bytes);
        $blocks   = '';
        if( $animated )
        {
            $blocks = $gift->decode($bytes);
        }
        $img = @imagecreatefromgif( $imageFile );

        if( !$img )
        {
            throw new \Exception('Could not open '.$imageFile.'. Not a valid GIF file.' );
        }

        return new self(
            $img,
            $imageFile,
            imagesx( $img ),
            imagesy( $img ),
            IMAGETYPE_GIF,
            $blocks,
            $animated
        );

    }


    /**
     * Resize
     * @param int $newWidth
     * @param int $newHeight
     * @param string $mode
     * @param array $color ： available if $model='fill'
     * @param string $position ： available if $model='fill'
     *      top-left  top-center  top-right
     *      center-left  center  center-right
     *      bottom-left  bottom-center  bottom-right
     * @return Gd
     * @throws \Exception
     */
    public function Resize(int $newWidth, int $newHeight, string $mode='fit', array $color=[255,255,255], string $position='center' )
    {
        $resizeWidth  = $this->width;
        $resizeHeight = $this->height;
        switch ($mode)
        {
            case 'exact':
                $resizeWidth  = $newWidth;
                $resizeHeight = $newHeight;
                break;
            case 'fill':
                return $this->fill($newWidth, $newHeight, $position, $color);
                break;
            case 'width':
                $resizeWidth = $newWidth;
                $resizeHeight = $this->height/($this->width/$newWidth);
                break;
            case 'height':
                $resizeHeight = $newHeight;
                $resizeWidth = $this->width/($this->height/$newHeight);
                break;
            case 'fit':
                $ratio  = $this->width / $this->height;
                $resizeWidth  = $newWidth;
                $resizeHeight = round($newWidth / $ratio);
                if( ($resizeWidth > $newWidth) || ($resizeHeight > $newHeight) )
                {
                    $resizeHeight = $newHeight;
                    $resizeWidth  = $newHeight * $ratio;
                }
                break;
            default:
                throw new \Exception(sprintf('Invalid resize mode "%s".', $mode));
        }
        return $this->_resize($resizeWidth, $resizeHeight);
    }

    /**
     * _resize
     * @param int $newWidth
     * @param int $newHeight
     * @param int $targetX
     * @param int $targetY
     * @param int $srcX
     * @param int $srcY
     * @return $this
     */
    public function _resize(int $newWidth, int $newHeight, int $targetX = 0, int $targetY = 0, int $srcX = 0, int $srcY = 0)
    {
        if ( $this->animated )
        {
            // Animated GIF
            $gift = new GifHelper();
            $this->blocks = $gift->resize($this->blocks, $newWidth, $newHeight);
        }
        else
        {
            $newImage = imagecreatetruecolor($newWidth, $newHeight);
            if( IMAGETYPE_PNG === $this->type )
            {
                static::alphaSetting($newImage, true);
            }

            imagecopyresampled(
                $newImage,
                $this->img,
                $targetX,
                $targetY,
                $srcX,
                $srcY,
                $newWidth,
                $newHeight,
                $this->width,
                $this->height
            );
            // Free old of tmp resource
            imagedestroy( $this->img );
            $this->img = $newImage;
        }

        $this->width  = $newWidth;
        $this->height = $newHeight;
        return $this;
    }

    /**
     * fill
     * @param $fillWidth
     * @param $fillHeight
     * @param string $position ： available if $model='fill'
     *      top-left  top-center  top-right
     *      center-left  center  center-right
     *      bottom-left  bottom-center  bottom-right
     * @param array $color ： available if $model='fill'
     * @return Gd
     */
    public function fill($fillWidth, $fillHeight, $position = 'center', array $color=[255,255,255])
    {
        $newImage = imagecreatetruecolor($fillWidth, $fillHeight);
        static::alphaSetting($newImage, true, $color);
        list($x,$y) = $this->GetPosition(
            $fillWidth,
            $fillHeight,
            $this->width,
            $this->height,
            $position
        );
        imagecopyresampled(
            $newImage,
            $this->img,
            $x,
            $y,
            0,
            0,
            $this->width,
            $this->height,
            $this->width,
            $this->height
        );

        imagedestroy($this->img);

        $this->img = $newImage;
        $this->width  = $fillWidth;
        $this->height = $fillHeight;

        return $this;
    }

    /**
     * WaterMark
     * @param string $waterImg
     * @param string $position
     *      top-left  top-center  top-right
     *      center-left  center  center-right
     *      bottom-left  bottom-center  bottom-right
     * @param int $offsetX
     * @param int $offsetY
     * @return Gd
     */
    public function WaterMark(string $waterImg, string $position='bottom-right', int $offsetX=0, int $offsetY=0)
    {
        $waterImg = $this->getImg($waterImg);
        $waterImgWidth  = imagesx($waterImg);
        $waterImgHeight = imagesy($waterImg);
        list($x,$y) = static::getPosition($this->width,$this->height, $waterImgWidth, $waterImgHeight, $position);
        imagecopy($this->img, $waterImg, $x-$offsetX, $y-$offsetY, 0, 0, $waterImgWidth, $waterImgHeight);
        imagedestroy($waterImg);
        return $this;
    }

    /**
     * Text
     * @param string $text
     * @param string $font
     * @param int $size
     * @param int $direction
     * @param int $x
     * @param int $y
     * @param array $color
     * @return Gd
     * @throws \Exception
     */
    public function Text(string $text, int $x=20, int $y=50, string $font='hwxk.ttf', int $size=20, array $color=[255,255,255], int $direction=0)
    {
        if( count($color)<3 )
        {
            throw new \Exception("color data error");
        }
        $color = imagecolorallocate($this->img,(int)$color[0],(int)$color[1],(int)$color[2]);
        imagettftext($this->img,$size,$direction,$x,$y,$color,$font,$text);
        return $this;
    }

    /**
     * Crop
     * @param int $x
     * @param int $y
     * @param $width
     * @param $height
     * @return Gd
     */
    public function Crop(int $x, int $y, $width, $height)
    {
        $newImage = imagecreatetruecolor($width, $height);
        static::alphaSetting($newImage, true, [255,255,255]);
        imagecopyresampled(
            $newImage,
            $this->img,
            0,
            0,
            $x,
            $y,
            $width,
            $height,
            $width,
            $height
        );
        imagedestroy($this->img);

        $this->img = $newImage;
        $this->width  = $width;
        $this->height = $height;
        return $this;
    }

    /**
     * getImg
     * @param string $waterImg
     * @return resource
     * @throws \Exception
     */
    private function getImg(string $waterImg)
    {
        switch( self::getImgInfo($waterImg) )
        {
            case IMAGETYPE_PNG:
                $img = imagecreatefrompng($waterImg);
                static::alphaSetting($img, true);
                break;
            case IMAGETYPE_JPEG:
                $img = imagecreatefromjpeg($waterImg);
                break;
            case IMAGETYPE_GIF:
                $img = imagecreatefromgif($waterImg);
                break;
            default:
                throw new \Exception('water image type does not supported');

        }
        return $img;
    }

    /**
     * Save
     * @param string $newFile
     * @param int $quality : available while image type is jpg
     * @param bool $interlace : available while image type is jpg
     * @param int $permission
     * @throws \Exception
     */
    public function Save(string $newFile, $quality = 85, $interlace = false, $permission = 0755)
    {
        $targetDir = dirname($newFile);    // $file's directory
        if (false === is_dir($targetDir))  // Check if $file's directory exist
        {
            // Create and set default perms to 0755
            if( !mkdir($targetDir, $permission, true) )
            {
                throw new \Exception('Cannot create '.$targetDir);
            }
        }

        switch ( $this->type )
        {
            case IMAGETYPE_GIF :
                if( $this->animated )
                {
                    $blocks = $this->blocks;
                    $gift = new GifHelper();
                    $hex = $gift->encode($blocks);
                    file_put_contents($newFile, pack('H*', $hex));
                }
                else
                {
                    imagegif($this->img, $newFile);
                }
                break;

            case IMAGETYPE_PNG :
                // PNG is lossless and does not need compression. Although GD allow values 0-9 (0 = no compression), we leave it alone.
                imagepng($this->img, $newFile);
                break;

            default:
                // Defaults to jpeg
                $quality = ($quality > 100) ? 100 : $quality;
                $quality = ($quality < 0) ? 0 : $quality;
                imageinterlace($this->img, $interlace);
                imagejpeg($this->img, $newFile, $quality);
        }
    }

    /**
     * getPosition
     * @param int $bgWidth
     * @param int $bgHeight
     * @param int $imageWidth
     * @param int $imageHeight
     * @param string $position
     * @return array
     * @throws \Exception
     */
    private function getPosition(int $bgWidth, int $bgHeight, int $imageWidth, int $imageHeight, string $position='center') :array
    {
        $x = 0;
        $y = 0;
        switch ($position)
        {
            case self::TOP_LEFT:
                $x = 0;
                $y = 0;
                break;
            case self::TOP_CENTER:
                $x = (int)round(($bgWidth / 2) - ($imageWidth / 2));
                $y = 0;
                break;
            case self::TOP_RIGHT:
                $x = $bgWidth - $imageWidth;
                $y = 0;
                break;
            case self::CENTER_LEFT:
                $x = 0;
                $y = (int)round(($bgHeight / 2) - ($imageHeight / 2));
                break;
            case self::CENTER_RIGHT:
                $x = $bgWidth - $imageWidth;
                $y = (int)round(($bgHeight / 2) - ($imageHeight / 2));
                break;
            case self::BOTTOM_LEFT:
                $x = 0;
                $y = $bgHeight - $imageHeight;
                break;
            case self::BOTTOM_CENTER:
                $x = (int)round(($bgWidth / 2) - ($imageWidth / 2));
                $y = $bgHeight - $imageHeight;
                break;
            case self::BOTTOM_RIGHT:
                $x = $bgWidth - $imageWidth;
                $y = $bgHeight - $imageHeight;
                break;
            case self::CENTER:
                $x = (int)round(($bgWidth / 2) - ($imageWidth / 2));
                $y = (int)round(($bgHeight / 2) - ($imageHeight / 2));
                break;
            default:
                throw new \Exception('Invalid position '. $position);

        }

        return [$x, $y];
    }


}