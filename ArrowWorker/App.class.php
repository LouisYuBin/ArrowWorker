<?php
/**
 * User: Louis
 * Date: 2016/8/1 19:47
 */

namespace ArrowWorker;

/**
 * 应用加载/启动类
 * Class App
 * @package ArrowWorker
 */
class App
{

    /**
     * RunApp 执行应用
     * @author Louis
     */
    public static function RunApp()
    {
        switch (APP_TYPE)
        {
            case 'cli':
                Console::StartProcessor();
                break;
            case 'swHttp':
                Swoole::Http();
                break;
            case 'fpm':
                Router::Start();
                break;
            default:
                exit('application type not defined.');
        }

    }



}
