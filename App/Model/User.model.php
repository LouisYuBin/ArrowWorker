<?php
/**
 * Created by PhpStorm.
 * User: louis
 * Date: 11/15/17
 * Time: 5:43 PM
 */

namespace App\Model;


use ArrowWorker\Driver;

class User
{
    //查询单条记录
    public function GetOne()
    {
        return Driver::Db()->Table("test")->Where('id=1')->Col('alias')->Get();
    }

    //查询单条记录
    public function GetList()
    {
        return Driver::Db()->Table("test")->Where('id>0')->Col('alias')->Limit(0,20)->Find();
    }

    //写入数据
    public function Insert()
    {
        $data = [
            'alias' => 'ArrowWorker',
            'extra' => "An efficient and "
        ];
        return Driver::Db()->Table("test")->Where('id>0')->Insert($data);
    }

    //写入数据
    public function UpdateById($id)
    {
        $data = [
            'alias' => 'ArrowWorker',
            'extra' => "A php demonize framework "
        ];
        return Driver::Db()->Table("test")->Where("id={$id}")->Update($data);
    }

    //删除数据
    public function DeleteById($id)
    {
        return Driver::Db()->Table("test")->Where("id={$id}")->Delete();
    }
}