<?php

namespace ArrowWorker\Library\Image;


interface ImageInterface
{

    /**
     * Open specified image
     * @param string $imageFile
     */
    public static function Open(string $imageFile);

    /*
     * create a blank image
     * @param int $width
     * @param int $height
     * @param array $bg
     * @param string $type
     * */
    public static function Create(int $width, int $height, array $bg = [255, 255, 255, 1], string $type = 'GIF');

    /*
     * Resize an image to a given width, height and mode.
     * @param int $newWidth
     * @param int $newHeight
     * @param string $mode
     * @param array $color ： available if $model='fill'
     * @param string $position ： available if $model='fill'
     *      top-left  top-center  top-right
     *      center-left  center  center-right
     *      bottom-left  bottom-center  bottom-right
     * */
    public function Resize(int $newWidth, int $newHeight, string $mode = 'fit', array $color = [255, 255, 255, 1], string $position = 'center');

    /*
     * add watermark on the image
     * @param string $waterImg
     * @param string $position
     *      top-left  top-center  top-right
     *      center-left  center  center-right
     *      bottom-left  bottom-center  bottom-right
     * @param int $offsetX
     * @param int $offsetY
     * */
    public function AddWatermark(string $waterImg, string $position = 'bottom-right', int $offsetX = 0, int $offsetY = 0);

    /*
     * WriteText : write text on the image
     * @param string $text
     * @param string $font
     * @param int $size
     * @param int $direction
     * @param int $x
     * @param int $y
     * @param array $color
     * */
    public function WriteText(string $text, int $x = 20, int $y = 50, string $font = 'cn_PianPianQingShuShouXie.ttf', int $size = 20, array $color = [255, 255, 255, 1]);

    /*
     * Crop the image to the given dimension and position.
     * @param int $x
     * @param int $y
     * @param $width
     * @param $height
     * */
    public function Crop(int $x, int $y, int $width, int $height);

    /*
     * add a gif frame to the back of it
     * @param $frame
     * @param int $delayTime
     * */
    public function AddFrame($frame, int $delayTime = 200);

    /*
     * add a gif frame on the front of it
     * @param $frame
     * @param int $delayTime
     * */
    public function AddFrontFrame($frame, int $delayTime = 200);


}