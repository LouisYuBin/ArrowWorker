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
	private $where   = "";

	/**
	 * @var string
	 */
	private $column  = "*";

	/**
	 * @var string
	 */
	private $table   = "";

	/**
	 * @var string
	 */
	private $limit   = "";

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
	private $having  = "";

	/**
	 * SqlBuilder constructor.
	 * @param null $instance
	 */
	public function __construct($instance=null)
    {
        //todo
    }

	/**
	 * @return \ArrowWorker\Driver\Db\Mysqli
	 */
    private function getDb()
    {
        return Db::GetDb();
    }


	/**
	 * @param string $where
	 * @return $this
	 */
	public function Where(string $where)
    {
        $this->where  = ($where != '') ? " where {$where} " : '';
        return $this;
    }


	/**
	 * @param string $table
	 * @return $this
	 */
	public function Table(string $table)
    {
        $this->table = $table;
        return $this;
    }


	/**
	 * @param array $column
	 * @return $this
	 */
	public function Col(array $column)
    {
        $this->column  = ( $column=="" ) ? "*" : implode(',', $column);
        return $this;
    }


	/**
	 * @param int $start
	 * @param int $num
	 * @return $this
	 */
	public function Limit(int $start, int $num)
    {
        $this->limit = " limit {$start},{$num} ";
        return $this;
    }


	/**
	 * @param string $orderBy
	 * @return $this
	 */
	public function OrderBy(string $orderBy)
    {
        $this->orderBy = $orderBy;
        return $this;
    }


	/**
	 * @param  string $groupBy
	 * @return $this
	 */
	public function GroupBy(string $groupBy)
    {
        $this->groupBy = ($groupBy != "") ? " group by {$groupBy} " : '' ;
        return $this;
    }


	/**
	 * @param string $having
	 * @return $this
	 */
	public function Having(string $having)
    {
        $this->having  = ($having != "") ? " having {$having} " : '';
        return $this;
    }


	/**
	 * @param bool $isMaster
	 * @param int $slaveIndex
	 * @return array
	 */
	public function Find(bool $isMaster=false, int $slaveIndex=0)
    {
        $result = $this->getDb()->Query( $this->parseSelect(), $isMaster, $slaveIndex );
        return $result===false ? [] : $result;
    }


	/**
	 * @param bool $isMaster
	 * @param int $slaveIndex
	 * @return array
	 */
	public function Get(bool $isMaster=false, int $slaveIndex=0 )
    {
        $data = $this->getDb()->Query( $this->parseSelect() ,$isMaster, $slaveIndex );
        return ($data===false) ? [] : ( count($data)>0 ? $data[0] : [] );
    }

	/**
	 * @param array $data
	 * @return array
	 */
	public function Insert(array $data)
    {
        $column = implode(',', array_keys($data));
        $values = "'".implode("','", $data)."'";
        return $this->getDb()->Execute("insert into {$this->table}({$column}) values({$values})");
    }


	/**
	 * @param array $data
	 * @return array
	 * @throws \Exception
	 */
	public function Update(array $data)
    {
        $update = '';
        foreach ($data as $key=>$val)
        {
            $update = $update.$key."='".$val."',";
        }
        $update = substr($update,0,-1);
        return $this->getDb()->Execute("update {$this->table} set {$update} {$this->where}");
    }


	/**
	 * @return array
	 */
	public function delete()
    {
        return $this->getDb()->Execute("delete from {$this->table} {$this->where}");
    }


	/**
	 * @return string
	 */
	public function parseSelect() :string
    {
        return trim("select  {$this->column} from {$this->table} {$this->where} {$this->groupBy} {$this->having} {$this->limit}");
    }


}