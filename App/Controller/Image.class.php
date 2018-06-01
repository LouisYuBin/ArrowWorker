<?php
/**
 * User: Louis
 * Date: 2016/8/2
 * Time: 10:35
 */

namespace App\Controller;

use ArrowWorker\Lib\Image\Image as Img;
use ArrowWorker\Response;

class Image
{

    function Index()
    {
        $photoDir = APP_PATH.DIRECTORY_SEPARATOR.APP_RUNTIME_DIR.'/Upload/Photo/';
        Img::Open($photoDir.'chengbao.jpg')
        ->Text("咏鹅",1300,800,'zh-cn/XinDiLvDou.otf',50)
        ->Text("-唐代Louis",1480,900,'zh-cn/XinDiLvDou.otf',36)
        ->Text("鹅鹅鹅，曲项向天歌",1200,1000,'zh-cn/XinDiLvDou.otf',50)
        ->Text("白毛浮绿水，红掌拨清波。",1200,1100,'zh-cn/XinDiLvDou.otf',50)
        ->Text('Louis Wish U a happy International Children\'s Day!',300,150,'en-us/krazykool.ttf',39)
        ->WaterMark($photoDir.'sunshine.png','top-left',-60,-60)
        //->Text("奶爸",105,210,'zh-cn/XinDiLvDou.otf',33,[0,0,0,1])
            ->Resize(600,600)
            //->Crop(0,0,600,600)
        ->Save($photoDir.'chengbao.liuyi.jpg');
        Response::Write('done');
    }


}
