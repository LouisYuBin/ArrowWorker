<?php

namespace App\Service;

use ArrowWorker\Config;
use ArrowWorker\Driver;
use ArrowWorker\Loader;

class Project
{
    
    private static $config;

    public function __construct()
    {
        self::$config = Config::Get();
    }
    
    public function add()
    {
        $method = Loader::Classes("Method");
        $method -> godDamIt();
        return "app -> service -> user -> add";
    }

    public function testDb()
    {
        $userModel = Loader::Model('Project');
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

    public function testRedisSet()
    {
        return Driver::Cache() -> Set("louis","good");
    }

    public function testRedisGet()
    {
        return  Driver::Cache() -> Get('louis');
    }

}

