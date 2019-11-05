<?php
/**
 * By yubin at 2019/3/4 11:31 AM.
 */

namespace ArrowWorker;


use ArrowWorker\Driver\Db\Mysqli;
use ArrowWorker\Driver\Db\Pdo;
use ArrowWorker\Driver\Db\Pool;
use ArrowWorker\Driver\Db\SqlBuilder;

/**
 * Class Db
 * @package ArrowWorker
 */
class Db
{


    /**
     *
     */
    const DEFAULT_ALIAS = 'default';


    /**
     * @param string $table
     * @param string $alias
     * @return SqlBuilder
     */
    public static function Table(string $table, string $alias=self::DEFAULT_ALIAS) : SqlBuilder
    {
        return (new SqlBuilder($alias))->Table($table);
    }

    /**
     * @param array $config
     */
    public static function Init( array $config )
    {
        Pool::Init($config);
        var_dump('Db pool initialized');
    }

    /**
     * @param string $alias
     * @return false|Mysqli|Pdo
     */
    public static function Get( string $alias=self::DEFAULT_ALIAS )
    {
        return Pool::GetConnection($alias);
    }

    /**
     *
     */
    public static function Release()
    {
        Pool::Release();
    }

}