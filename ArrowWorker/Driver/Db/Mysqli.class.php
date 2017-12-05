<?php
/**
 * User: Arrow
 * Date: 2016/8/1
 * Time: 19:51
 */

namespace ArrowWorker\Driver\Db;
use ArrowWorker\Driver\Db AS db;


/**
 * Class Mysqli
 * @package ArrowWorker\Driver\Db
 */
class Mysqli extends db
{


	/**
	 * 初始化数据库连接类
	 * @param array $config
	 * @param string $alias
	 * @return Mysqli
	 */
	static function Init(array $config, string $alias)
    {
        //存储配置
        if ( !isset( self::$config[$alias] ) )
        {
            self::$config[$alias] = $config;
        }

        //设置当前
        self::$dbCurrent = $alias;

        if(!self::$instance)
        {
            self::$instance = new self($config);
        }

        return self::$instance;
    }

	/**
	 * @param array $config
	 * @return \mysqli
	 */
	private function connectInit(array $config)
    {
        //建立连接
        @$Conn = new \mysqli($config['host'],$config['userName'],$config['password'],$config['dbName'],$config['port']);
        //捕捉错误
        if($Conn->connect_errno)
        {
            exit($Conn->connect_error);
        }
        //初始化字符集
        $Conn -> query("set names '".self::$config[self::$dbCurrent]['charset']."'");
        return $Conn;
    }

	/**
	 * 连接数据库
	 * @param bool $isMaster
	 * @param int $connectNum
	 * @return \mysqli
	 */
	protected function getConnection(bool $isMaster=false, int $connectNum=0)
    {
        if( $isMaster==true || self::$config[self::$dbCurrent]['seperate']==0 )
        {
            return $this -> connectMaster();
        }
        return $this -> connectSlave($connectNum);
    }


	/**
	 * 检测并连接主库
     * @return \mysqli
	 */
	private function connectMaster()
    {
        if( !isset( self::$connPool[self::$dbCurrent]['master'] ) )
        {
            self::$connPool[self::$dbCurrent]['master'] = $this -> connectInit( self::$config[self::$dbCurrent]['master'] );
        }
        return self::$connPool[self::$dbCurrent]['master'];
    }


	/**
	 * 检测并连接从库
	 * @param int $slaveIndex
	 * @return \mysqli
	 */
	private function connectSlave(int $slaveIndex=0)
    {
        $slaveCount = count(self::$config[self::$dbCurrent]['slave']);
        $slave = ( $slaveIndex==0 || $slaveIndex>=$slaveCount || $slaveIndex<0 ) ? mt_rand( 0, $slaveCount-1 ) : $slaveIndex;

        if ( !isset( self::$connPool[self::$dbCurrent]['slave'][$slave] ) )
        {
            self::$connPool[self::$dbCurrent]['slave'][$slave] = $this -> connectInit(self::$config[self::$dbCurrent]['slave'][$slave]);
        }
        return self::$connPool[self::$dbCurrent]['slave'][$slave];
    }


	/**
	 * 查询
	 * @param string $sql
	 * @param bool $isMaster
	 * @param int $connectNum
	 * @return array|bool
	 */
	public function query(string $sql, bool $isMaster=false, int $connectNum=0)
    {

        $result = $this -> getConnection($isMaster,$connectNum) -> query($sql);
        if($result)
        {
            $return = [];
            while($row = $result->fetch_assoc())
            {
                $return[] = $row;
            }
            return $return;
        }
        else
        {
            return false;
        }

    }


	/**
	 * execute 写入或更新
	 * @param string $sql
	 * @return array
	 */
	public function execute(string $sql)
    {
        $conn = $this -> getConnection(true);
        return [
            'result'       => $conn->query($sql),
            'affectedRows' => $conn->affected_rows,
            'insertId'     => $conn->insert_id
        ];
    }


	/**
	 * Begin 开始事务
	 */
	public function Begin()
    {
        $conn = $this -> getConnection(true);
        $conn -> autocommit(false);
        $conn -> begin_transaction();
    }



	/**
	 * Commit 提交事务
	 */
	public function Commit()
    {
        $conn = $this -> getConnection(true);
        $conn -> commit();
        $conn -> autocommit(true);
    }


	/**
	 * Rollback 事务回滚
	 */
	public function Rollback()
    {
        $conn = $this -> getConnection(true);
        $conn -> rollback();
        $conn -> autocommit(true);
    }


	/**
	 * Autocommit 是否自动提交
	 * @param bool $flag
	 */
	public function Autocommit(bool $flag)
    {
        $this -> getConnection(true) -> autocommit($flag);
    }


	/**
	 * Table 启动sql组合
	 * @param string $table
	 * @return SqlBuilder
	 */
	public static function Table(string $table)
    {
        $sqlBuilder = new SqlBuilder();
        return $sqlBuilder -> Table($table, self::$config[self::$dbCurrent]['driver']);
    }


}
