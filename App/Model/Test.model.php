<?php
/**
 * Created by PhpStorm.
 * User: louis
 * Date: 11/15/17
 * Time: 5:43 PM
 */

namespace App\Model;


use ArrowWorker\Driver;

class Test
{
    public function test()
    {
        $db = Driver::Db();
        return $db->Table("test")->Where('id=1')->get();
    }
}