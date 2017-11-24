<?php
/**
 * Created by PhpStorm.
 * User: louis
 * Date: 11/15/17
 * Time: 5:43 PM
 */

namespace App\Model;


use ArrowWorker\Driver;

class ArrowWorker
{
    //查询单条记录
    public function GetOne()
    {
        $column = ['itemName', 'itemIntro','author', 'authorIntro'];
        return Driver::Db()->Table("project")->Where('id=1')->Col($column)->Get();
    }

    //查询单条记录
    public function GetList()
    {
        $column = ['itemName', 'itemIntro','author', 'authorIntro'];
        return Driver::Db()->Table("project")->Where('id>0')->Col($column)->Limit(0,20)->Find();
    }

    //写入数据
    public function Insert()
    {
        $data = [
            'itemName' => 'ArrowWorker',
            'itemIntro' => "An efficient and easy-using php daemon framework."
        ];
        return Driver::Db()->Table("project")->Where('id>0')->Insert($data);
    }

    //写入数据
    public function UpdateById($id)
    {
        $data = [
            'itemName' => 'ArrowWorker',
            'itemIntro' => "A php demonize framework "
        ];
        return Driver::Db()->Table("project")->Where("id={$id}")->Update($data);
    }

    //删除数据
    public function DeleteById($id)
    {
        return Driver::Db()->Table("project")->Where("id={$id}")->Delete();
    }
}