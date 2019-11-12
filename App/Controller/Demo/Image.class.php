<?php
/**
 * User: Louis
 * Date: 2016/8/2
 * Time: 10:35
 */

namespace App\Controller\Demo;

use ArrowWorker\Lib\Image\Image as Img;
use ArrowWorker\Response;

class Image
{

    function Index()
    {
        $photoDir = APP_PATH.DIRECTORY_SEPARATOR.APP_RUNTIME_DIR.'/Upload/Photo/';
 /*       Img::Open($photoDir.'chengbao.jpg')
        ->WriteText("咏鹅",1300,800,'cn_XinDiLvDou.otf',50)
        ->WriteText("-唐代Louis",1480,900,'cn_XinDiLvDou.otf',36)
        ->WriteText("鹅鹅鹅，曲项向天歌",1200,1000,'cn_XinDiLvDou.otf',50)
        ->WriteText("白毛浮绿水，红掌拨清波。",1200,1100,'cn_XinDiLvDou.otf',50)
        ->WriteText('Louis Wish U a happy International Children\'s Day!',300,150,'en_krazykool.ttf',39)
        ->AddWatermark($photoDir.'sunshine.png','top-left',-60,-60)
        //->Text("奶爸",105,210,'zh-cn/XinDiLvDou.otf',33,[0,0,0,1])
        ->Resize(600,600)
            //->Crop(0,0,600,600)
        ->Save($photoDir.'chengbao.liuyi.jpg');*/

        $width = 200;
        $height= 200;
        $color = [255,255,255,1];
        $font = 'cn_BoLeYaYa.ttf';

        $life = Img::Create($width,$height,$color,'gif');

        $first = Img::Create($width,$height,$color,'jpeg');
        $first->WriteText("算命",70,80,$font,45,[0,0,0,1]);
        $first->WriteText("姻缘/财运/生子/吉凶",15,120,$font,20,[0,0,0,1]);
        $first->Save('first.jpg');
        $life->AddFrame($first,200);


        $second = Img::Create($width,$height,$color,'jpeg');
        $second->WriteText("于彬",60,80,$font,45,[0,0,0,1]);
        $second->WriteText("你的姻缘将至，",10,109,$font,20,[0,0,0,1]);
        $second->WriteText("给500大洋揭秘姻缘！",10,133,$font,20,[0,0,0,1]);
        $second->WriteText("收款账号见二维码",60,175,$font,16,[0,0,0,1]);
        $life->AddFrame($second,200);


        $third = Img::Create($width,$height,$color,'jpeg');
        $third->AddWatermark($photoDir.'WechatIMG4.jpeg','center');
        $life->AddFrame($third,300);


        $last = Img::Create($width,$height,$color,'jpeg');
        $last->WriteText("纯属玩笑，",30,90,$font,30,[0,0,0,1]);
        $last->WriteText("哈哈哈哈！",30,120,$font,30,[0,0,0,1]);
        $life->AddFrame($last,200);

        $life->AddFrontFrame(Img::Open($photoDir.'tuzi.gif')->Resize(200,200,'fill'));

        $third = Img::Create($width,$height,$color,'jpeg');
        $third->AddWatermark($photoDir.'WechatIMG4.jpeg','center');
        $life->AddFrame($third,300);

        $life->Save($photoDir.'gaoxiao.gif');

        Response::Write('done');
    }


}
