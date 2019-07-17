<?php
/**
 * User: Arrow
 * Date: 2016/8/1
 * Time: 19:51
 */

namespace ArrowWorker\Driver\Db;

use ArrowWorker\Config;
use ArrowWorker\Log;
use ArrowWorker\Swoole;
use \Swoole\Coroutine\Channel as swChan;


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
    const CONFIG_NAME       = 'Db';
    /**
     *
     */
    const SQL_LOG_NAME      = 'Sql';
    /**
     *
     */
    const DEFAULT_POOL_SIZE = 10;

    /**
     * @var array
     */
    private static $pool   = [];
    /**
     * @var array
     */
    private static $configs = [];

    private static $chanConnections = [

    ];

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
    private function __construct( array $config )
    {
        $this->_config = $config;
    }

    /**
     * @return false|Mysqli
     */
    private function _initConnection()
    {
        @$this->_conn = new \mysqli( $this->_config['host'],  $this->_config['userName'],  $this->_config['password'],  $this->_config['dbName'],  $this->_config['port'] );
        if ( $this->_conn->connect_errno )
        {
            Log::DumpExit( "connecting to mysql failed : " . $this->_conn->connect_error );
            return false;
        }

        if ( false === $this->_conn->query( "set names '" .  $this->_config['charset'] . "'" ) )
        {
            Log::Warning( "mysqi set names(charset) failed.", self::LOG_NAME );
        }
        return $this;
    }

    /**
     * @param string $alias
     * @return false|Mysqli
     */
    public static function GetConnection( $alias = 'default' )
    {
        $coId = Swoole::GetCid();
        if( isset(self::$chanConnections[$coId]) )
        {
            return self::$chanConnections[$coId];
        }

        _RETRY:
        $conn = self::$pool[$alias]->pop( 1 );
        if ( false === $conn )
        {
            goto _RETRY;
        }
        self::$chanConnections[Swoole::GetCid()] = $conn;
        return $conn;
    }

    public static function ReturnConnection(string $alias)
    {
        $coId = Swoole::GetCid();
        self::$pool[$alias]->push( self::$chanConnections[$coId]);
        unset(self::$chanConnections[$coId]);
    }

    /**
     * check config and initialize connection chan
     */
    public static function Init()
    {
        self::_initConfig();
        self::_initPool();
    }

    private static function _initConfig()
    {
        $config = Config::Get( self::CONFIG_NAME );
        if ( !is_array( $config ) || count( $config ) == 0 )
        {
            Log::Error( 'incorrect config', self::LOG_NAME );
            return ;
        }

        foreach ( $config as $index => $value )
        {
            if ( !isset( $value['host'] ) ||
                 !isset( $value['dbName'] ) ||
                 !isset( $value['userName'] ) ||
                 !isset( $value['password'] ) ||
                 !isset( $value['port'] ) ||
                 !isset( $value['charset'] ) )
            {
                Log::Error( "configuration for {$index} is incorrect. config : ".json_encode($value), self::LOG_NAME );
                continue;
            }

            $value['poolSize'] = isset($value['poolSize']) && (int)$value['poolSize']>0 ? (int)$value['poolSize'] : self::DEFAULT_POOL_SIZE;
            self::$configs[$index] = $value;
            self::$pool[$index] = new swChan( $value['poolSize'] );
        }
    }


    /**
     * fill connection pool
     */
    private static function _initPool()
    {
        foreach (self::$configs as $index=>$config)
        {
            for ($i=self::$pool[$index]->length(); $i<$config['poolSize']; $i++)
            {
                $conn = (new self( $config ))->_initConnection();
                if( false==$conn )
                {
                    Log::Warning("initialize mysqli connection failed, config : ",json_encode($config));
                    continue ;
                }
                self::$pool[$index]->push( $conn );
            }
        }
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
