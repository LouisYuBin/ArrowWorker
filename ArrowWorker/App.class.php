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
        set_time_limit(0);
        //设置运行日志级别
        error_reporting(self::$config['level']);

        if( APP_TYPE=='fpm' )
        {
            Router::Start();
        }
        else
        {
            Daemon::Start();
        }

    }




}
