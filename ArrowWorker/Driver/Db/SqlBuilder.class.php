<?php
/**
 * Created by PhpStorm.
 * User: louis
 * Date: 11/6/17
 * Time: 11:23 AM
 */

namespace ArrowWorker\Driver\Db;


class SqlBuilder
{
    private $where  = "";
    private $column = "*";
    private $table  = "";
    private $limit  = "";
    private $orderBy = "";
    private $groupBy = "";
    private $having  = "";

    private $Instance = null;

    public function __construct($instance)
    {
        $this->Instance = $instance;
    }

    public function where($where)
    {
        $where  = ($where != '') ? " where {$this->where} " : '';
        return $this;
    }

    public function table($table)
    {
        $this->table = $table;
        return $this;
    }

    public function col($column)
    {
        $this->column  = ( $column=="" ) ? "*" : $column;
        return $this;
    }

    public function limit($start, $num)
    {
        $this->limit = " limit {$start},{$num} ";
        return $this;
    }

    public function orderBy($orderBy)
    {
        $this->orderBy = $orderBy;
        return $this;
    }

    public function groupBy($groupBy)
    {
        $this->groupBy = ($groupBy != "") ? " group by {$groupBy} " : '' ;
        return $this;
    }

    public function having($having)
    {
        $this->having  = ($having != "") ? " having {$having} " : '';
        return $this;
    }

    public function find()
    {
        return $this->Instance->query( $this->parseSelect() );
    }

    public function get()
    {
        $result = $this->Instance->query( $this->parseSelect() );
        return count($result)>0 ? $result[0] : false;
    }

    public function insert($data)
    {
        if ( !is_array() && count($data)>0 )
        {
            throw new \Exception("inert data must be an array,just like ['name'=>'Louis']");
        }
        $column = '';
        $values = '';
        foreach ($data as $key=>$val)
        {
            $column = $column.$key.",";
            $values = $values."'".$val."',";
        }
        $column = substr($column,0,-1);
        $values = substr($values,0,-1);
        return $this->Instance->execute("insert into {$this->table}({$column}) values({$values})");
    }

    public function update($data)
    {
        if ( !is_array() && count($data)>0 )
        {
            throw new \Exception("update data must be an array,just like ['name'=>'Louis']");
        }
        $update = '';
        foreach ($data as $key=>$val)
        {
            $update = $update.$key."='".$val."',";
        }
        $update = substr($update,0,-1);
        return $this->Instance->execute("update {$this->table} set {$update} {$this->where}");
    }

    public function delete()
    {
        return $this->Instance->execute("delete from {$this->table} {$this->where}");
    }

    public function parseSelect()
    {
        if ( $this->table=="" )
        {
            throw new \Exception("please specify the table you wanna query.");
        }
        return "select  {$this->column} from {$this->table} where {$this->where} {$this->groupBy} {$this->having} {$this->limit}";
    }

}