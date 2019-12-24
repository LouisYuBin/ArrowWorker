<?php
/**
 * User: Arrow
 * Date: 2016/8/1
 * Time: 19:51
 */

namespace ArrowWorker\Component\Db;

use ArrowWorker\Log;

class Mysqli implements DbInterface
{
	
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
     * @return bool
     */
    public function InitConnection()
    {
        @$this->_conn = new \mysqli( $this->_config['host'],  $this->_config['userName'],  $this->_config['password'],  $this->_config['dbName'],  $this->_config['port'] );
        if ( $this->_conn->connect_errno )
        {
            Log::Dump( __CLASS__.'::'.__METHOD__."connect failed : " . $this->_conn->connect_error, Log::TYPE_WARNING, self::MODULE_NAME );
            return false;
        }

        if ( false === $this->_conn->query( "set names '" .  $this->_config['charset'] . "'" ) )
        {
            Log::Dump( __CLASS__.'::'.__METHOD__."set names({$this->_config['charset']}) failed.", Log::TYPE_WARNING, self::MODULE_NAME  );
        }
        return true;
    }


    /**
     * 查询
     * @param string $sql
     * @return array|bool
     */
    public function Query( string $sql )
    {
        $result = $this->_query( $sql );
        if( false===$result )
        {
            return $result;
        }

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

    /**
     * @param string $sql
     *
     * @return bool|\mysqli_result
     */
    private function _query(string $sql)
    {
        $isRetried = false;
        _RETRY:
        $result = @$this->_conn->query( $sql );
        if(false !== $result && !is_null($result) )
        {
            Log::Debug( $sql, self::SQL_LOG_NAME );
            return $result;
        }

        if( 0!==@$this->_conn->errno && !$isRetried )  //check connection status, reconnect if connection error
        {
            Log::Dump( __CLASS__.'::'.__METHOD__." Mysqli::query Error, error no : {$this->_conn->errno}, error message : {$this->_conn->error}, reconnecting...", Log::TYPE_NOTICE, self::MODULE_NAME );
            $this->InitConnection();
            $isRetried = true;
            goto _RETRY;
        }

        @Log::Dump( "Sql Error : {$sql}, error no : {$this->_conn->errno}, error message : {$this->_conn->error}", Log::TYPE_WARNING, self::MODULE_NAME );
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
