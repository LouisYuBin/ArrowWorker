<?php
/**
 * User: Arrow
 * Date: 2016/8/1
 * Time: 19:51
 */

namespace ArrowWorker\Component\Db;

use ArrowWorker\Container;
use ArrowWorker\Log\Log;

class Mysqli implements DbInterface
{

    /**
     * @var \mysqli
     */
    private $conn;

    /**
     * @var array
     */
    private $config = [];

    private $container;

    /**
     * @param Container $container
     * @param array $config
     */
    public function __construct(Container $container, array $config)
    {
        $this->container = $container;
        $this->config = $config;
    }

    /**
     * @return bool
     */
    public function InitConnection()
    {
        @$this->conn = $this->container->Make(\mysqli::class, [$this->config['host'], $this->config['userName'], $this->config['password'], $this->config['dbName'], $this->config['port']]);
        if ($this->conn->connect_errno) {
            Log::Dump(__CLASS__ . '::' . __METHOD__ . "connect failed : " . $this->conn->connect_error, Log::TYPE_WARNING, self::MODULE_NAME);
            return false;
        }

        if (false === $this->conn->query("set names '" . $this->config['charset'] . "'")) {
            Log::Dump(__CLASS__ . '::' . __METHOD__ . "set names({$this->config['charset']}) failed.", Log::TYPE_WARNING, self::MODULE_NAME);
        }
        return true;
    }


    /**
     * 查询
     * @param string $sql
     * @return array|bool
     */
    public function Query(string $sql)
    {
        $result = $this->_query($sql);
        if (false === $result) {
            return $result;
        }

        $field = $this->_parseFieldType($result);
        $return = [];
        while ($row = $result->fetch_assoc()) {
            foreach ($row as $key => &$val) {
                settype($val, $field[$key]);
            }
            $return[] = $row;
        }
        $result->free();
        return $return;
    }

    /**
     * @param \mysqli_result $result
     * @return array
     */
    private function _parseFieldType(\mysqli_result $result): array
    {
        $fields = [];
        while ($info = $result->fetch_field()) {
            switch ($info->type) {
                case MYSQLI_TYPE_BIT:
                case MYSQLI_TYPE_TINY:
                case MYSQLI_TYPE_SHORT:
                case MYSQLI_TYPE_LONG:
                case MYSQLI_TYPE_LONGLONG:
                case MYSQLI_TYPE_INT24:
                    $type = 'int';
                    break;
                case MYSQLI_TYPE_FLOAT:
                case MYSQLI_TYPE_DOUBLE:
                case MYSQLI_TYPE_DECIMAL:
                case MYSQLI_TYPE_NEWDECIMAL:
                    $type = 'float';
                    break;
                default:
                    $type = 'string';
            }
            $fields[$info->name] = $type;
        }
        return $fields;
    }


    /**
     * execute 写入或更新
     * @param string $sql
     * @return array
     */
    public function Execute(string $sql)
    {
        return [
            'result'       => $this->_query($sql),
            'affectedRows' => $this->conn->affected_rows,
            'insertId'     => $this->conn->insert_id
        ];
    }

    /**
     * @param string $sql
     *
     * @return bool|\mysqli_result
     */
    private function _query(string $sql)
    {
        $isRetried = false;
        _RETRY:
        $result = @$this->conn->query($sql);
        if (false !== $result && !is_null($result)) {
            Log::Debug($sql, [], self::SQL_LOG_NAME);
            return $result;
        }

        if (0 !== @$this->conn->errno && !$isRetried)  //check connection status, reconnect if connection error
        {
            Log::Dump(__CLASS__ . '::' . __METHOD__ . " Mysqli::query Error, error no : {$this->conn->errno}, error message : {$this->conn->error}, reconnecting...", Log::TYPE_NOTICE, self::MODULE_NAME);
            $this->InitConnection();
            $isRetried = true;
            goto _RETRY;
        }

        @Log::Dump("Sql Error : {$sql}, error no : {$this->conn->errno}, error message : {$this->conn->error}", Log::TYPE_WARNING, self::MODULE_NAME);
        return false;
    }

    /**
     * Begin 开始事务
     */
    public function Begin()
    {
        $this->conn->autocommit(false);
        for ($i = 0; $i < 6; $i++) {
            if ($this->conn->begin_transaction()) {
                return true;
            }
            $this->conn->ping();
        }
        return false;
    }

    /**
     * Commit 提交事务
     */
    public function Commit()
    {
        $result = false;
        for ($i = 0; $i < 6; $i++) {
            $result = $this->conn->commit();
            if ($result) {
                break;
            }
            $this->conn->ping();
        }
        $this->conn->autocommit(true);
        return $result;
    }

    /**
     * Rollback 事务回滚
     */
    public function Rollback()
    {
        $result = false;
        for ($i = 0; $i < 6; $i++) {
            $result = $this->conn->rollback();
            if ($result) {
                break;
            }
        }
        $this->conn->autocommit(true);
        return $result;
    }

    /**
     * Autocommit 是否自动提交
     * @param bool $flag
     */
    public function Autocommit(bool $flag)
    {
        $this->conn->autocommit($flag);
    }

}
