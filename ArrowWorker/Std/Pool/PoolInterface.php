<?php

namespace ArrowWorker\Std\Pool;

use ArrowWorker\Container;


interface PoolInterface
{

    /**
     * @param int
     */
    const DEFAULT_POOL_SIZE = 10;


    public function __construct(Container $container, array $presetConfig, array $userConfig = []);

    /**
     * @param string $alias
     * @return mixed
     */
    public static function Get(string $alias = 'default');

    /**
     * @return void
     */
    public function Release(): void;
}