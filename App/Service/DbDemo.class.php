<?php

namespace App\Service;

use ArrowWorker\Config;
use ArrowWorker\Driver;
use ArrowWorker\Loader;

class DbDemo
{
    
    private static $config;

    public function __construct()
    {
        self::$config = Config::Get();
    }

    public function testDb()
    {
        $userModel = Loader::Model('ArrowWorker');
        $db = Driver::Db();
        $db -> Begin();
        $oneProject    =  $userModel->GetOne();
        $projectList   =  $userModel->GetList();
        $insertProject =  $userModel->Insert();
        $db -> Rollback();
        $insertProject =  $userModel->Insert();
        $insertProject =  $userModel->Insert();
        $insertProject =  $userModel->Insert();
        $insertProject =  $userModel->Insert();
        $insertProject =  $userModel->Insert();
        $insertProject =  $userModel->Insert();
        $updateProject =  $userModel->UpdateById( 1 );
        $deleteProject =  $userModel->DeleteById( 100 );
        $db -> Commit();
        return [
            'oneUser'    => $oneProject,
            'userList'   => $projectList,
            'insertUser' => $insertProject,
            'updateUser' => $updateProject,
            'deleteUser' => $deleteProject
        ];
    }


}

