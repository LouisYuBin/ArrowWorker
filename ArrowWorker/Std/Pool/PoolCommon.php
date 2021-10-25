<?php

namespace ArrowWorker\Std\Pool;

use ArrowWorker\Container;

class PoolCommon
{

    /**
     * @var array
     */
    protected array $pool = [];

    /**
     * @var array
     */
    protected array $config = [];

    protected Container $container;

    protected static $instance;
}