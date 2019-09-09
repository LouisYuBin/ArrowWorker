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
     * @return SqlBuilder
     */
    public static function Table(string $table, string $alias=self::DEFAULT_ALIAS, string $driver=self::DEFAULT_DRIVER) : SqlBuilder
    {
        return (new SqlBuilder($alias, $driver))->Table($table);
    }

    /**
     * @param array $config
     * @param string $driver
     */
    public static function Init( array $config, string $driver=self::DEFAULT_DRIVER )
    {
        $driver::Init($config);
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
     * @param string $driver
     */
    public static function Release( string $driver=self::DEFAULT_DRIVER )
    {
        $driver::ReturnConnection();
    }

}