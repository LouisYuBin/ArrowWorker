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
    const TYPE_UDP = 4;

    /**
     *
     */
    const TYPE_BASE = 5;

    /**
     *
     */
    const TYPE_WORKER = 6;

    /**
     *
     */
    const CONTROLLER_NAMESPACE = '\\' . APP_DIR . '\\' . APP_CONTROLLER_DIR . '\\';

    /**
     * RunApp 执行应用
     * @author Louis
     */
    public static function Run()
    {
        self::_initOptions();
        Daemon::Start();
    }

    private static function _initOptions()
    {
        set_time_limit( 0 );
        $options = Config::Get('Global');
        if( !is_array($options) )
        {
            return ;
        }

        foreach ($options as $option=>$value)
        {
            ini_set($option,$value);
        }
    }

    public static function GetController()
    {
        return self::CONTROLLER_NAMESPACE;
    }

}
