<?php
/**
 * Created by PhpStorm.
 * User: louis
 * Date: 11/6/17
 * Time: 11:23 AM
 */

namespace ArrowWorker\Driver\Db;

use ArrowWorker\Driver\Db\Mysqli;

/**
 * Class SqlBuilder sql语句组合、执行类
 * @package ArrowWorker\Driver\Db
 */
class SqlBuilder
{

    private $alias = 'default';

    /**
     * @var Mysqli;
     */
    private $driver = 'Mysqli';

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
     * @param string $alias
     * @param string $driver
     */
    public function __construct( string $alias = 'default', string $driver='Mysqli' )
    {
        $this->alias  = $alias;
        $this->driver = $driver;
    }

    /**
     * @return \ArrowWorker\Driver\Db\Mysqli
     */
    private function _getDb()
    {
        return $this->driver::GetConnection($this->alias);
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

     * @return false|array
     */
    public function Find()
    {
        return $this->_getDb()->Query( $this->_parseSelect() );
    }


    /**
     * @return false|array
     */
    public function Get()
    {
        $data = $this->_getDb()->Query( $this->_parseSelect() );
        return ($data === false) ? false : (count( $data ) > 0 ? $data[0] : []);
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
     */
    public function Update( array $data )
    {
        $update = '';
        foreach ( $data as $key => $val )
        {
            $update .= is_array( $val ) && count( $val ) > 0 ? "{$key}={$key}{$val[0]}, " : "{$key}='{$val}', ";
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