<?php

namespace ArrowWorker;


interface PoolInterface
{

    /**
     * @param int
     */
    const DEFAULT_POOL_SIZE = 10;

    /**
     * @param array $alias
     * @param array $config
     * @return mixed
     */
    public static function Init( array $alias, array $config) : void ;

    /**
     * @param string $alias
     * @return mixed
     */
    public static function Get( string $alias = 'default' );

    /**
     * @return void
     */
    public static function Release() : void ;
}