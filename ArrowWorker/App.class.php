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
        ini_set('memory_limit', '512M');
        Daemon::Start();
    }

}
