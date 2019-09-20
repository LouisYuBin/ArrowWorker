<?php
/**
 * By yubin at 2019-09-19 18:00.
 */

namespace ArrowWorker\Driver;


use ArrowWorker\Log;

interface Db
{
    /**
     *
     */
    const LOG_NAME     = 'Db';

    /**
     *
     */
    const SQL_LOG_NAME = 'Sql';


    public function __construct( array $config );

    /**
     * @return bool
     */
    public function InitConnection();


    /**
     * 查询
     * @param string $sql
     * @return array|bool
     */
    public function Query( string $sql );

    /**
     * execute 写入或更新
     * @param string $sql
     * @return array
     */
    public function Execute( string $sql );


    /**
     * Begin 开始事务
     */
    public function Begin();

    /**
     * Commit 提交事务
     */
    public function Commit();

    /**
     * Rollback 事务回滚
     */
    public function Rollback();


    /**
     * Autocommit 是否自动提交
     * @param bool $flag
     */
    public function Autocommit( bool $flag );

}