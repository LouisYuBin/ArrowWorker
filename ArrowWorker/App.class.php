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
     *
     */
    const TYPE_HTTP = 1;

    /**
     *
     */
    const TYPE_WEBSOCKET = 2;

    /**
     *
     */
    const TYPE_TCP = 3;

    /**
     *
     */
    const TYPE_UDP = 3;

    /**
     *
     */
    const CONTROLLER_NAMESPACE = '\\' . APP_DIR . '\\' . APP_CONTROLLER_DIR . '\\';

    /**
     * RunApp 执行应用
     * @author Louis
     */
    public static function RunApp()
    {
        set_time_limit( 0 );
        ini_set( 'memory_limit', '512M' );
        Daemon::Start();
    }

}
