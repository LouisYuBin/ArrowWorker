<?php
/**
 * By yubin at 2019/3/4 11:31 AM.
 */

namespace ArrowWorker;


use ArrowWorker\Driver\Db\SqlBuilder;

class Db extends Driver
{
    /**
     *
     */
    const COMPONENT_TYPE = 'Db';

    /**
     *
     */
    const DEFAULT_ALIAS = 'default';


    /**
     * @param string $table
     * @param string $alias
     * @return \ArrowWorker\Driver\Db\SqlBuilder
     */
    public static function Table(string $table, string $alias=self::DEFAULT_ALIAS)
    {
        static::_init(static::COMPONENT_TYPE, $alias);
        return (new SqlBuilder())->Table($table);
    }

}