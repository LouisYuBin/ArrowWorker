<?php
/**
 * User: Arrow
 * Date: 2016/8/1
 * Time: 19:51
 */

namespace ArrowWorker\Driver\Db;

use ArrowWorker\Config;
use ArrowWorker\Log;
use \Swoole\Coroutine\Channel as swChan;


/**
 * Class Mysqli
 * @package ArrowWorker\Driver\Db
 */
class Mysqli
{
    const LOG_NAME          = 'Db';
    const CONFIG_NAME       = 'Db';
    const SQL_LOG_NAME      = 'Sql';
    const DEFAULT_POOL_SIZE = 10;

    //数据库连接池
    private static $pool = [];

    /**
     * @var mysqli
     */
    private $_conn;

    private $_config = [];

    private function __construct( array $config )
    {
        $this->_config = $config;
        @$this->_conn = new \mysqli( $config['host'], $config['userName'], $config['password'], $config['dbName'], $config['port'] );
        if ( $this->_conn->connect_errno )
        {
            Log::DumpExit( "connecting to mysql failed : " . $this->_conn->connect_error );
        }

        if ( false === $this->_conn->query( "set names '" . $config['charset'] . "'" ) )
        {
            Log::Warning( "mysqi set names(charset) failed.", self::LOG_NAME );
        }
    }

    public static function GetConnection( $alias = 'default' )
    {
        _RETRY:
        $conn = self::$pool[$alias]->pop( 1 );
        if ( false === $conn )
        {
            goto _RETRY;
        }
        return $conn;
    }

    /**
     * 初始化数据库连接类
     */
    public static function Init()
    {
        //存储配置
        $config = Config::Get( self::CONFIG_NAME );
        if ( !is_array( $config ) || count( $config ) == 0 )
        {
            Log::Error( 'incorrect config', self::LOG_NAME );
            return ;
        }

        foreach ( $config as $index => $value )
        {
            self::$pool[$index] = new swChan( isset( $value['port'] ) ? (int)$value['port'] : self::DEFAULT_POOL_SIZE );

            if ( !isset( $value['host'] ) ||
                 !isset( $value['dbName'] ) ||
                 !isset( $value['userName'] ) ||
                 !isset( $value['password'] ) ||
                 !isset( $value['port'] ) ||
                 !isset( $value['charset'] ) )
            {
                Log::Error( "configuration for {$index} is incorrect.", self::LOG_NAME );
                continue;
            }

            self::$pool[$index]->push( new self( $value ) );
        }

    }

    /**
     * 查询
     * @param string $sql
     * @return array|bool
     */
    public function Query( string $sql )
    {
        Log::Debug( $sql, self::SQL_LOG_NAME );

        $result = $this->_conn->query( $sql );
        if ( !$result )
        {
            Log::Error( $sql, self::SQL_LOG_NAME );
            return false;
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
        $result = $this->_conn->query( $sql );

        Log::Debug( $sql, self::SQL_LOG_NAME );
        if ( false === $result )
        {
            Log::Error( $sql, self::SQL_LOG_NAME );
        }

        return [
            'result'       => $result,
            'affectedRows' => $this->_conn->affected_rows,
            'insertId'     => $this->_conn->insert_id
        ];
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
