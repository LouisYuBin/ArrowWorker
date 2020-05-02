<?php
/**
 * By yubin at 2019-09-11 11:24.
 */

namespace ArrowWorker\Component\Db;

use ArrowWorker\Container;

class Pdo implements DbInterface
{

    public function __construct(Container $container, array $config)
    {

    }

    /**
     * @return bool
     */
    public function InitConnection()
    {

    }


    /**
     * 查询
     * @param string $sql
     * @return array|bool
     */
    public function Query(string $sql)
    {

    }

    /**
     * execute 写入或更新
     * @param string $sql
     * @return array
     */
    public function Execute(string $sql)
    {

    }


    /**
     * Begin 开始事务
     */
    public function Begin()
    {

    }

    /**
     * Commit 提交事务
     */
    public function Commit()
    {

    }

    /**
     * Rollback 事务回滚
     */
    public function Rollback()
    {

    }


    /**
     * Autocommit 是否自动提交
     * @param bool $flag
     */
    public function Autocommit(bool $flag)
    {

    }
}