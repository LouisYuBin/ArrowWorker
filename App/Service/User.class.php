<?php

namespace App\Service;

use ArrowWorker\Config;
use ArrowWorker\Loader;

class User
{
    
    private static $config;

    public function __construct()
    {
        self::$config = Config::App();
    }
    
    public function add()
    {
        $method = Loader::Classes("Method");
        $method -> godDamIt();
        return "app -> service -> user -> add";
    }

    public function testDb()
    {
        $userModel = Loader::Model('User');
        $oneUser    =  $userModel->GetOne();
        $userList   =  $userModel->GetList();
        $insertUser =  $userModel->Insert();
        $updateUser =  $userModel->UpdateById( 1 );
        $deleteUser =  $userModel->DeleteById( 100);
        return [
            'oneUser'    => $oneUser,
            'userList'   => $userList,
            'insertUser' => $insertUser,
            'updateUser' => $updateUser,
            'deleteUser' => $deleteUser
        ];
    }

}

