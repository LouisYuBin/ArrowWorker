<?php
/**
 * Created by PhpStorm.
 * User: louis
 * Date: 11/15/17
 * Time: 5:43 PM
 */

namespace App\Model;


use ArrowWorker\Db;

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
        $column = ['itemName', 'itemIntro','author', 'authorIntro'];
        return Db::Table("project")->Where('id>1')->Column($column)->Limit(0,1)->Get();
    }

    //查询单条记录
    public static function GetList()
    {
        $column = ['itemName', 'itemIntro','author', 'authorIntro'];
        return Db::Table("project")->Where('id>0')->Column($column)->Limit(0,2)->Find();
    }

    //写入数据
    public function Insert()
    {
        $data = [
            'itemName' => 'ArrowWorker',
            'itemIntro' => "An efficient and easy-using php daemon framework."
        ];
        return Db::Table("project")->Where('id>0')->Insert($data);
    }

    //写入数据
    public function UpdateById($id)
    {
        $data = [
            'itemName' => 'ArrowWorker',
            'itemIntro' => "A php demonize framework "
        ];
        return Db::Table("project")->Where("id={$id}")->Update($data);
    }

    //删除数据
    public function DeleteById($id)
    {
        return Db::Table("project")->Where("id={$id}")->Delete();
    }
}