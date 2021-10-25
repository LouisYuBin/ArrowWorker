<?php

namespace ArrowWorker;

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