<?php
/**
 * User: Louis
 * Date: 2016/8/3
 * Time: 11:38
 */

namespace App\Model;
use ArrowWorker\Model as model;

class Index extends model
{

    //示例
    public function example($para)
    {
        $sql    = 'select '.$para.' from tableName';
        return $this -> db -> query($sql);
    }

}
