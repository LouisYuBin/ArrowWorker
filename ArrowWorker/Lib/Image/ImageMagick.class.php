<?php
/**
 * User: louis
 * Time: 18-5-24 下午12:53
 */

namespace ArrowWorker\Lib\Image;

/**
 * Class ImageMagick
 * @package Image
 */
class ImageMagick
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
    const IMAGETYPE_GIF  = 'GIF';
    const IMAGETYPE_JPEG = 'JPEG';
    const IMAGETYPE_PNG = 'PNG';

    /*
     * font path
     */
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
     * ImageMagick constructor.
     * @param $img
     * @param string $imageFile
     * @param int $width
     * @param int $height
     * @param int $type
     */
    private function __construct(\Imagick $img, string $imageFile, int $width, int $height, string $type, $blocks = '', bool $animated = false )
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
     * Open
     * @param string $imageFile
     * @return Gd
     */
    public static function Open(string $imageFile) : self
    {
        var_dump($imageFile);
        if ( !file_exists( $imageFile ) ) {
            throw new \Exception( sprintf('Could not open image file "%s"', $imageFile) );
        }

        $imagick = new \Imagick( $imageFile );
        $animated = false;
        if ($imagick->getImageIterations() > 0) {
            $animated = true;
        }

        return new self(
            $imagick,
            $imageFile,
            $imagick->getImageWidth(),
            $imagick->getImageHeight(),
            $imagick->getImageFormat(),
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
     * @return $this
     * @throws \Exception
     */
    public function Resize(int $newWidth, int $newHeight, string $mode='fit', array $color=[255,255,255,1], string $position='center' )
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
                //return $this->Resize($newWidth, $newHeight)->fill($newWidth, $newHeight, $position, $color);
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
        if ( $this->type==static::IMAGETYPE_GIF )
        {
            $imagick = $this->img->coalesceImages();
            foreach ($imagick as $frame) {
                $frame->resizeImage($newWidth, $newHeight, \Imagick::FILTER_BOX, 1, false);
                $frame->setImagePage($newWidth, $newHeight, 0, 0);
            }
            $this->img = $imagick->deconstructImages();
        }
        else
        {
            $result = $this->img->resizeImage($newWidth, $newHeight, \Imagick::FILTER_LANCZOS, 1, false);
            if( !$result )
            {
                throw new \Exception('resizeImage failed');
            }
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
     * @return $this
     */
    public function fill($fillWidth, $fillHeight, $position = 'center', array $color=[255,255,255,1])
    {
        $newImg = new \Imagick();
        list($x, $y) = $this->getPosition($fillWidth,$fillHeight, $this->width, $this->height, $position);

        if( $this->type==static::IMAGETYPE_GIF )
        {
            $imagick = $this->img->coalesceImages();
            foreach($imagick as $frame)
            {
                $draw = new \ImagickDraw();
                $draw->composite(
                    $frame->getImageCompose(),
                    $x,
                    $y,
                    $this->width,
                    $this->height,
                    $frame
                );
                $eachPage = new \Imagick();
                $eachPage->newImage($fillWidth, $fillHeight,"rgba({$color[0]},{$color[1]},{$color[2]},{$color[3]})");
                $eachPage->drawImage($draw);
                $newImg->addImage($eachPage);
                $newImg->setImageFormat(static::IMAGETYPE_GIF);
            }
        }
        else
        {
            $draw = new \ImagickDraw();
            $draw->composite(
                $this->img->getImageCompose(),
                $x,
                $y,
                $this->width,
                $this->height,
                $this->img
            );

            $newImg->newImage($fillWidth, $fillHeight,"rgba({$color[0]},{$color[1]},{$color[2]},{$color[3]})");
            $newImg->drawImage($draw);
            $newImg->setImageFormat($this->type);
        }
        $this->width  = $fillWidth;
        $this->height = $fillHeight;
        $this->img = $newImg;
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
     * @return $this
     */
    public function WaterMark(string $waterImg, string $position='bottom-right', int $offsetX=0, int $offsetY=0)
    {
        $waterMark = new \Imagick($waterImg);
        list($x,$y) = $this->getPosition($this->width,$this->height,$waterMark->getImageWidth(),$waterMark->getImageHeight(),$position);
        $draw = new \ImagickDraw();
        $draw->composite(
            $waterMark->getImageCompose(),
            $x - $offsetX,
            $y - $offsetY,
            $waterMark->getImageWidth(),
            $waterMark->getimageheight(),
            $waterMark
        );
        if( $this->type==static::IMAGETYPE_GIF )
        {
            $imagick = $this->img->coalesceImages();
            foreach($imagick as $frame)
            {
                $frame->drawImage($draw);
            }
            $this->img = $imagick->deconstructImages();
        }
        else
        {
            $this->img->drawImage($draw);
        }
        return $this;
    }


    /**
     * @param string $text
     * @param int $x
     * @param int $y
     * @param string $font
     * @param int $size
     * @param array $color
     * @param int $direction
     * @return $this
     * @throws \Exception
     */
    public function Text(string $text, int $x=20, int $y=50, string $font='zh-cn/PianPianQingShuShouXie.ttf', int $size=20, array $color=[255,255,255,1], int $direction=0)
    {
        if(count($color)<4)
        {
            throw new \Exception('color data illegal');
        }
        $draw = new \ImagickDraw();
        $draw->setFont(static::FONT_PATh.$font);
        $draw->setFillColor(new \ImagickPixel("rgba({$color[0]}, {$color[1]}, {$color[2]}, {$color[3]})"));
        $draw->setFontSize($size);
        $draw->annotation($x, $y, $text);

        if( $this->type==static::IMAGETYPE_GIF )
        {
            $imagick = $this->img->coalesceImages();
            foreach($imagick as $frame)
            {
                $frame->drawImage($draw);
            }
            $this->img = $imagick->deconstructImages();
        }
        else
        {
            $this->img->drawImage($draw);
        }
        return $this;
    }

    public function AddFrame(ImageMagick $frame, int $delayTime=500)
    {
        if( $this->type !=static::IMAGETYPE_GIF )
        {
            return $this;
        }

        if( $frame->type==static::IMAGETYPE_GIF )
        {
            $frames = $frame->img->coalesceImages();
            foreach($frames as $eachFrame)
            {
                $tmpImagick = new \Imagick();
                $tmpImagick->readImageBlob($eachFrame);
                $this->img->addImage($tmpImagick);
                $this->img->setImageDelay( $tmpImagick->getImageDelay() );
                $tmpImagick->destroy();
            }
        }
        else
        {
            $this->img->addImage($frame->img);
            $this->img->setImageDelay($delayTime);
        }

        return $this;
    }

    public function AddFrontFrame(ImageMagick $frame, int $delayTime=500)
    {
        if( $this->type != static::IMAGETYPE_GIF )
        {
            return $this;
        }
        $newGif = new \Imagick();

        if( $frame->type==static::IMAGETYPE_GIF )
        {
            $frames = $frame->img->coalesceImages();
            foreach($frames as $eachFrame)
            {
                $tmpImagick = new \Imagick();
                $tmpImagick->readImageBlob($eachFrame);
                $newGif->addImage($tmpImagick);
                $newGif->setImageDelay( $tmpImagick->getImageDelay() );
                $newGif->setImageFormat( static::IMAGETYPE_GIF );
                $tmpImagick->destroy();
            }
        }
        else
        {
            $newGif->addImage($frame->img);
            $newGif->setImageDelay($delayTime);
        }

        $frames = $this->img->coalesceImages();
        foreach($frames as $eachFrame)
        {
            $tmpImagick = new \Imagick();
            $tmpImagick->readImageBlob($eachFrame);
            $newGif->addImage($tmpImagick);
            $newGif->setImageDelay( $tmpImagick->getImageDelay() );
            $tmpImagick->destroy();
        }

        $this->img->destroy();
        $this->img = $newGif;

        return $this;
    }

    public function Create(int $width, int $height, array $bg=[255,255,255,1], string $type='GIF')
    {
        if( count($bg)<4 )
        {
            throw new \Exception('background color format illegal!');
        }
        $img = new \Imagick();

        if($type!='GIF')
        {
            $img->newImage($width,$height,"rgba({$bg[0]},{$bg[1]},{$bg[2]},{$bg[3]})", $type);
        }

        return new self(
            $img,
            '',
            $width,
            $height,
            $type,
            false
        );
    }

    /**
     * Crop
     * @param int $x
     * @param int $y
     * @param $width
     * @param $height
     * @return $this
     */
    public function Crop(int $x, int $y, $width, $height)
    {
        if( $this->type==static::IMAGETYPE_GIF )
        {
            $newImg = new \Imagick();
            $newImg->setFormat(static::IMAGETYPE_GIF);

            $imagick = $this->img->coalesceImages();
            foreach($imagick as $frame)
            {
                if( !$frame->cropImage($width,$height,$x,$y) )
                {
                    throw new \Exception('cropImage error');
                }
                $frame->setImagePage($width, $height, 0, 0);
                $eachPage = new \Imagick();
                $eachPage->readImageBlob($frame);
                $newImg->addImage($eachPage);
                $newImg->setImageDelay( $eachPage->getImageDelay() );
            }
            $this->img->destroy();
            $this->img = $newImg;
        }
        else
        {
            if( !$this->img->cropImage($width,$height,$x,$y) )
            {
                throw new \Exception('cropImage error');
            }
        }
        $this->width  = $width;
        $this->height = $height;
        return $this;
    }


    /**
     * Save
     * @param string $newFile
     * @param int $quality : available while image type is jpg
     * @param bool $interlace : available while image type is jpg
     * @param int $permission
     * @throws \Exception
     */
    public function Save(string $newFile, $quality = 100, $interlace = false, $permission = 0755)
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
            case static::IMAGETYPE_GIF :
                $this->img->writeImages($newFile, true); // Support animated image. Eg. GIF
                break;
            case static::IMAGETYPE_PNG :
                // PNG is lossless and does not need compression. Although GD allow values 0-9 (0 = no compression), we leave it alone.
                $this->img->setImageFormat($this->type);
                $this->img->writeImage($newFile);
                break;
            default:
                // Defaults to jpeg
                $quality = ($quality > 100) ? 100 : $quality;
                $quality = ($quality < 0) ? 0 : $quality;
                if ($interlace) {
                    $this->img->setImageInterlaceScheme(\Imagick::INTERLACE_JPEG);
                }
                $this->img->setImageFormat(static::IMAGETYPE_JPEG);
                $this->img->setImageCompression(\Imagick::COMPRESSION_JPEG);
                $this->img->setImageCompressionQuality($quality);
                $this->img->writeImage($newFile); // Single frame image. Eg. JPEG
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