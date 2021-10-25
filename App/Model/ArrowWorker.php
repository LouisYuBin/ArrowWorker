<?php
/**
 * Created by PhpStorm.
 * User: louis
 * Date: 11/15/17
 * Time: 5:43 PM
 */

namespace App\Model;


use ArrowWorker\Component\Db\Query;

/**
 * Class ArrowWorker
 * @package App\Model
 */
class ArrowWorker
{

    /**
     * 查询单条记录
     * @return array
     */
    public static function GetOne()
    {
        $column = [
            'itemName',
            'itemIntro',
            'author',
            'authorIntro',
        ];
        return Query::table("project")->where('id>1')->column($column)->limit(0, 1)->get();
    }

    //查询单条记录
    public static function GetList()
    {
        $column = [
            'itemName',
            'itemIntro',
            'author',
            'authorIntro',
        ];
        return Query::table("project")->where('id>0')->column($column)->limit(0, 2)->find();
    }

    //写入数据
    public function Insert()
    {
        $data = [
            'itemName'  => 'ArrowWorker',
            'itemIntro' => "An efficient and easy-using php daemon framework.",
        ];
        return Query::table("project")->where('id>0')->insert($data);
    }

    //写入数据
    public function UpdateById($id)
    {
        $data = [
            'itemName'  => 'ArrowWorker',
            'itemIntro' => "A php demonize framework ",
        ];
        return Query::table("project")->where("id={$id}")->update($data);
    }

    //删除数据
    public function DeleteById($id)
    {
        return Query::table("project")->where("id={$id}")->delete();
    }
}