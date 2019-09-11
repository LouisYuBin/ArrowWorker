<?php
/**
 * User: Arrow
 * Date: 2016/8/1
 * Time: 19:51
 */

namespace ArrowWorker\Driver\Db;

use ArrowWorker\Log;

/**
 * Class Mysqli
 * @package ArrowWorker\Driver\Db
 */
class Mysqli
{
    /**
     *
     */
    const LOG_NAME          = 'Db';

    /**
     *
     */
    const SQL_LOG_NAME      = 'Sql';


    /**
     * @var \mysqli
     */
    private $_conn;

    /**
     * @var array
     */
    private $_config = [];

    /**
     * Mysqli constructor.
     * @param array $config
     */
    public function __construct( array $config )
    {
        $this->_config = $config;
    }

    /**
     * @return false|Mysqli
     */
    public function _initConnection()
    {
        @$this->_conn = new \mysqli( $this->_config['host'],  $this->_config['userName'],  $this->_config['password'],  $this->_config['dbName'],  $this->_config['port'] );
        if ( $this->_conn->connect_errno )
        {
            Log::Error( "connecting to mysql failed : " . $this->_conn->connect_error, self::LOG_NAME );
            return false;
        }

        if ( false === $this->_conn->query( "set names '" .  $this->_config['charset'] . "'" ) )
        {
            Log::Warning( "mysqi set names(charset) failed.", self::LOG_NAME );
        }
        return $this;
    }


    /**
     * 查询
     * @param string $sql
     * @return array|bool
     */
    public function Query( string $sql )
    {
        $result = $this->_query( $sql );
        $field  = $this->_parseFieldType( $result );
        $return = [];
        while ( $row = $result->fetch_assoc() )
        {
            foreach ( $row as $key => &$val )
            {
                settype( $val, $field[$key] );
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
    private function _parseFieldType( \mysqli_result $result ) : array
    {
        $fields = [];
        while ( $info = $result->fetch_field() )
        {
            switch ( $info->type )
            {
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
    public function Execute( string $sql )
    {
        return [
            'result'       => $this->_query( $sql ),
            'affectedRows' => $this->_conn->affected_rows,
            'insertId'     => $this->_conn->insert_id
        ];
    }

    private function _query(string $sql)
    {
        $isRetried = false;
        _RETRY:
        $result = $this->_conn->query( $sql );
        if(false !== $result )
        {
            Log::Debug( $sql, self::SQL_LOG_NAME );
            return $result;
        }

        if( true===$isRetried )
        {
            Log::Error( "Sql Error : {$sql}", self::SQL_LOG_NAME );
            return false;
        }

        if( $this->_conn->ping() )  //check and reconnect
        {
            $isRetried = true;
            goto _RETRY;
        }

        Log::Error( "connection is not available : {$sql}", self::SQL_LOG_NAME );
        return false;
    }

    /**
     * Begin 开始事务
     */
    public function Begin()
    {
        $this->_conn->autocommit( false );
        for ( $i = 0; $i < 6; $i++ )
        {
            if ( $this->_conn->begin_transaction() )
            {
                return true;
            }
            $this->_conn->ping();
        }
        return false;
    }

    /**
     * Commit 提交事务
     */
    public function Commit()
    {
        $result = false;
        for ( $i = 0; $i < 6; $i++ )
        {
            $result = $this->_conn->commit();
            if ( $result )
            {
                break;
            }
            $this->_conn->ping();
        }
        $this->_conn->autocommit( true );
        return $result;
    }

    /**
     * Rollback 事务回滚
     */
    public function Rollback()
    {
        $result = false;
        for ( $i = 0; $i < 6; $i++ )
        {
            $result = $this->_conn->rollback();
            if ( $result )
            {
                break;
            }
        }
        $this->_conn->autocommit( true );
        return $result;
    }

    /**
     * Autocommit 是否自动提交
     * @param bool $flag
     */
    public function Autocommit( bool $flag )
    {
        $this->_conn->autocommit( $flag );
    }

}
