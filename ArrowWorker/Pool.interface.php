<?php
/**
 * By yubin at 2019-09-20 16:58.
 */

namespace ArrowWorker;


/**
 * Interface Pool
 * @package ArrowWorker\Driver
 */
interface Pool
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
    public static function GetConnection( $alias = 'default' );

    /**
     * @return void
     */
    public static function Release() : void ;
}