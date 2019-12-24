<?php

namespace ArrowWorker\Component;

class Worker
{
    //服务配置
    protected static $config = [];
    //服务对象
    protected static $instance;

    /**
     * running user
     * @var string
     */
    protected static $user = 'root';

    /**
     * running group
     * @var string
     */
    protected static $group = 'root';

    protected function __construct(array $config)
    {
        self::$config = $config;
    }


}