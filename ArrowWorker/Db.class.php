<?php
/**
 * By yubin at 2019/3/4 11:31 AM.
 */

namespace ArrowWorker;


use ArrowWorker\Driver\Db\SqlBuilder;
use ArrowWorker\Driver\Db\Mysqli;

/**
 * Class Db
 * @package ArrowWorker
 */
class Db extends Driver
{

    /**
     *
     */
    const DEFAULT_DRIVER = 'Mysqli';

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
    public static function Init( string $driver=self::DEFAULT_DRIVER)
    {
        $driver::Init();
    }

    /**
     * @param string $driver
     */
    public static function FillPool( string $driver=self::DEFAULT_DRIVER)
    {
        while (true)
        {
            $driver::FillPool();
        }
    }

}