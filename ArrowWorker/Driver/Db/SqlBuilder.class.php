<?php
/**
 * Created by PhpStorm.
 * User: louis
 * Date: 11/6/17
 * Time: 11:23 AM
 */

namespace ArrowWorker\Driver\Db;

use ArrowWorker\Driver\Db;

/**
 * Class SqlBuilder sql语句组合、执行类
 * @package ArrowWorker\Driver\Db
 */
class SqlBuilder
{

    /**
     * @var string
     */
    private $where = "";

    /**
     * @var string
     */
    private $column = "*";

    /**
     * @var string
     */
    private $table = "";

    /**
     * @var string
     */
    private $limit = "";

    /**
     * @var string
     */
    private $orderBy = "";

    /**
     * @var string
     */
    private $groupBy = "";

    /**
     * @var string
     */
    private $having = "";

    /**
     * @var string
     */
    private $join = "";

    /**
     * @var string
     */
    private $forUpdate = "";

    /**
     * SqlBuilder constructor.
     * @param null $instance
     */
    public function __construct( $instance = null )
    {
        //todo
    }

    /**
     * @return \ArrowWorker\Driver\Db\Mysqli
     */
    private function _getDb()
    {
        return Db::GetConnection();
    }


    /**
     * @param string $where
     * @return $this
     */
    public function Where( string $where )
    {
        $this->where = ($where != '') ? " where {$where} " : '';
        return $this;
    }


    /**
     * @param string $table
     * @return $this
     */
    public function Table( string $table )
    {
        $this->table = $table;
        return $this;
    }

    /**
     * @param array $column
     * @return $this
     */
    public function Column( array $column )
    {
        $this->column = ($column == "") ? "*" : implode( ',', $column );
        return $this;
    }

    /**
     * @param int $start
     * @param int $num
     * @return $this
     */
    public function Limit( int $start, int $num )
    {
        $this->limit = " limit {$start},{$num} ";
        return $this;
    }

    /**
     * @param array $join
     * @return $this
     */
    public function Join( array $join )
    {
        $this->join = implode( ' ', $join );
        return $this;
    }

    /**
     * @param string $orderBy
     * @return $this
     */
    public function OrderBy( string $orderBy )
    {
        $this->orderBy = $orderBy;
        return $this;
    }

    /**
     * @param  string $groupBy
     * @return $this
     */
    public function GroupBy( string $groupBy )
    {
        $this->groupBy = ($groupBy != "") ? " group by {$groupBy} " : '';
        return $this;
    }

    /**
     * @param string $having
     * @return $this
     */
    public function Having( string $having )
    {
        $this->having = ($having != "") ? " having {$having} " : '';
        return $this;
    }

    /**
     * @param bool $isForUpdate
     * @return $this
     */
    public function ForUpdate( bool $isForUpdate = false )
    {
        $this->forUpdate = $isForUpdate ? ' for update ' : '';
        return $this;
    }

    /**
     * @param bool $isMaster
     * @param int  $slaveIndex
     * @return array
     */
    public function Find( bool $isMaster = false, int $slaveIndex = 0 )
    {
        $result = $this->_getDb()->Query( $this->_parseSelect(), $isMaster, $slaveIndex );
        return $result === false ? [] : $result;
    }


    /**
     * @param bool $isMaster
     * @param int  $slaveIndex
     * @return array
     */
    public function Get( bool $isMaster = false, int $slaveIndex = 0 )
    {
        $data = $this->_getDb()->Query( $this->_parseSelect(), $isMaster, $slaveIndex );
        return ($data === false) ? [] : (count( $data ) > 0 ? $data[0] : []);
    }

    /**
     * @param array $data
     * @return array
     */
    public function Insert( array $data )
    {
        $column = implode( ',', array_keys( $data ) );
        $values = "'" . implode( "','", $data ) . "'";
        return $this->_getDb()->Execute( "insert into {$this->table}({$column}) values({$values})" );
    }

    /**
     * @param array $data
     * @return array
     * @throws \Exception
     */
    public function Update( array $data )
    {
        $update = '';
        foreach ( $data as $key => $val )
        {
            $update .= is_array( $val ) && count( $val ) > 0 ? "{$key}={$key}{$val}, " : "{$key}='{$val}', ";
        }
        $update = substr( $update, 0, -1 );
        return $this->_getDb()->Execute( "update {$this->table} set {$update} {$this->where}" );
    }

    /**
     * @return array
     */
    public function Delete()
    {
        return $this->_getDb()->Execute( "delete from {$this->table} {$this->where}" );
    }

    /**
     * @return string
     */
    public function _parseSelect() : string
    {
        return trim( "select  {$this->column} from {$this->table} {$this->join} {$this->where} {$this->groupBy} {$this->having} {$this->orderBy} {$this->limit} {$this->forUpdate}" );
    }


}