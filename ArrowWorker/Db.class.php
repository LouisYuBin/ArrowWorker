<?php
/**
 * By yubin at 2019/3/4 11:31 AM.
 */

namespace ArrowWorker;


use ArrowWorker\Driver\Db\Mysqli;
use ArrowWorker\Driver\Db\SqlBuilder;

/**
 * Class Db
 * @package ArrowWorker
 */
class Db extends Driver
{

    /**
     *
     */
    const DEFAULT_DRIVER = 'ArrowWorker\Driver\Db\Mysqli';

    /**
     *
     */
    const DEFAULT_ALIAS = 'default';


    /**
     * @param string $table
     * @param string $alias
     * @param string $driver
     * @return \ArrowWorker\Driver\Db\SqlBuilder
     */
    public static function Table(string $table, string $alias=self::DEFAULT_ALIAS, string $driver=self::DEFAULT_DRIVER)
    {
        return (new SqlBuilder($alias, $driver))->Table($table);
    }

    /**
     * @param string $driver
     */
    public static function Init( string $driver=self::DEFAULT_DRIVER )
    {
        $driver::Init();
    }

    /**
     * @param string $alias
     * @param string $driver
     * @return Mysqli
     */
    public static function Get( string $alias=self::DEFAULT_ALIAS, string $driver=self::DEFAULT_DRIVER )
    {
        return $driver::GetConnection($alias);
    }

    /**
     * @param string $alias
     * @param string $driver
     */
    public static function Release( string $alias=self::DEFAULT_ALIAS, string $driver=self::DEFAULT_DRIVER)
    {
        $driver::ReturnConnection($alias);
    }

}