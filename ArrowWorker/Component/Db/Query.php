<?php

namespace ArrowWorker\Component\Db;

class Query
{

    private string $alias = 'default';

    /**
     * @var string
     */
    private string $where = "";

    /**
     * @var string
     */
    private string $column = "*";

    /**
     * @var string
     */
    private string $table = "";

    /**
     * @var string
     */
    private string $limit = "";

    /**
     * @var string
     */
    private string $orderBy = "";

    /**
     * @var string
     */
    private string $groupBy = "";

    /**
     * @var string string
     */
    private string $having = "";

    /**
     * @var string
     */
    private string $join = "";

    /**
     * @var string
     */
    private string $forUpdate = "";

    /**
     * @param string $dbAlias
     */
    public function __construct(string $dbAlias = 'default')
    {
        $this->alias = $dbAlias;
    }

    public static function table(string $table, string $dbAlias = 'default')
    {
        return (new self($dbAlias))->setTable($table);
    }

    /**
     * @return Mysqli|Pdo|false
     */
    private function getConn()
    {
        return Pool::get($this->alias);
    }


    /**
     * @param string $where
     * @return $this
     */
    public function where(string $where): self
    {
        $this->where = empty($where) ? " where {$where} " : '';
        return $this;
    }


    /**
     * @param string $table
     * @return $this
     */
    public function setTable(string $table): self
    {
        $this->table = $table;
        return $this;
    }

    /**
     * @param array $column
     * @return $this
     */
    public function column(array $column): self
    {
        $this->column = empty($column) ? "*" : implode(',', $column);
        return $this;
    }

    /**
     * @param int $num
     * @param int $offset
     * @return $this
     */
    public function limit(int $num, int $offset=0): self
    {
        $this->limit = " limit {$offset},{$num} ";
        return $this;
    }

    /**
     * @param array $join
     * @return $this
     */
    public function join(array $join): self
    {
        $this->join = implode(' ', $join);
        return $this;
    }

    /**
     * @param string $orderBy
     * @return $this
     */
    public function orderBy(string $orderBy): self
    {
        $this->orderBy = $orderBy;
        return $this;
    }

    /**
     * @param string $groupBy
     * @return $this
     */
    public function groupBy(string $groupBy): self
    {
        $this->groupBy = ($groupBy != "") ? " group by {$groupBy} " : '';
        return $this;
    }

    /**
     * @param string $having
     * @return $this
     */
    public function having(string $having): self
    {
        $this->having = empty($having) ? " having {$having} " : '';
        return $this;
    }

    /**
     * @param bool $isForUpdate
     * @return $this
     */
    public function forUpdate(bool $isForUpdate = false): self
    {
        $this->forUpdate = $isForUpdate ? ' for update ' : '';
        return $this;
    }

    /**
     * @return false|array
     */
    public function find()
    {
        $conn = $this->getConn();
        if (false === $conn) {
            return false;
        }
        return $conn->Query($this->parseSelect());
    }


    /**
     * @return false|array
     */
    public function get()
    {
        $conn = $this->getConn();
        if (false === $conn) {
            return false;
        }
        $this->limit(1);
        $data = $conn->Query($this->parseSelect());
        return ($data === false) ? false : ($data[0] ?? []);
    }

    /**
     * @param array $data
     * @return bool|array
     */
    public function insert(array $data)
    {
        $conn = $this->getConn();
        if (false === $conn) {
            return false;
        }

        $column = implode(',', array_keys($data));
        $values = "'" . implode("','", $data) . "'";
        return $conn->Execute("insert into {$this->table}({$column}) values({$values})");
    }

    /**
     * @param array $data
     * @return bool|array
     */
    public function update(array $data)
    {
        $conn = $this->getConn();
        if (false === $conn) {
            return false;
        }

        $update = '';
        foreach ($data as $key => $val) {
            $update .= is_array($val) && count($val) > 0 ? "{$key}={$key}{$val[0]}, " : "{$key}='{$val}', ";
        }
        $update = substr($update, 0, -1);
        return $conn->execute("update {$this->table} set {$update} {$this->where}");
    }

    /**
     * @return bool|array
     */
    public function delete()
    {
        $conn = $this->getConn();
        if (false === $conn) {
            return false;
        }
        return $conn->execute("delete from {$this->table} {$this->where}");
    }

    /**
     * @return string
     */
    public function parseSelect(): string
    {
        return trim("select  {$this->column} from {$this->table} {$this->join} {$this->where} {$this->groupBy} {$this->having} {$this->orderBy} {$this->limit} {$this->forUpdate}");
    }


}