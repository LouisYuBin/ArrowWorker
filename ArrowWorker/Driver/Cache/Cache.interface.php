<?php
/**
 * User: Arrow
 * Date: 2016/8/1
 * Time: 19:49
 */

namespace ArrowWorker\Driver\Cache;


interface Cache
{

    /**
     * @param array $config
     */
    public function __construct( array $config );

    /**
     * @return bool
     */
    public function InitConnection() : bool;

    /**
     * Db 选择数据库
     * @param int $dbName
     * @return bool
     */
    public function Db(int $dbName) : bool;


    /**
     * Set : write cache
     * @param $key
     * @param $val
     * @return bool
     */
    public function Set(string $key, string $val) : bool;


    /**
     * Get : read cache
     * @param string $key
     * @return string|false
     */
    public function Get(string $key);


}