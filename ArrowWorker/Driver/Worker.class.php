<?php
/**
 * User: Arrow
 * Date: 2016/8/1
 * Time: 19:49
 */

namespace ArrowWorker\Driver;


class Worker
{
    //服务配置
    protected static $config = [];
    //服务对象
    protected static $daemonObj;

    /**
     * running user
     * @var string
     */
    protected static $_user = 'root';

    /**
     * running group
     * @var string
     */
    protected static $_group = 'root';

    protected function __construct(array $config)
    {
        self::$config = $config;
    }


}