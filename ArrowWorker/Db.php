<?php

namespace ArrowWorker;

use ArrowWorker\Component\Db\Mysqli;
use ArrowWorker\Component\Db\Pdo;
use ArrowWorker\Component\Db\Pool;
use ArrowWorker\Component\Db\SqlBuilder;

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
    }

    /**
     * @param string $alias
     * @return false|Mysqli|Pdo
     */
    public static function Get( string $alias=self::DEFAULT_ALIAS )
    {
        return Pool::Get($alias);
    }

    /**
     *
     */
    public static function Release()
    {
        Pool::Release();
    }

}