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
        $userModel = Loader::Model('Project');
        $ins = Driver::Db();
        $ins -> Begin();
        $oneProject    =  $userModel->GetOne();
        $projectList   =  $userModel->GetList();
        $insertProject =  $userModel->Insert();
        $updateProject =  $userModel->UpdateById( 1 );
        $deleteProject =  $userModel->DeleteById( 100 );
        $ins -> Commit();
        return [
            'oneUser'    => $oneProject,
            'userList'   => $projectList,
            'insertUser' => $insertProject,
            'updateUser' => $updateProject,
            'deleteUser' => $deleteProject
        ];
    }

}

