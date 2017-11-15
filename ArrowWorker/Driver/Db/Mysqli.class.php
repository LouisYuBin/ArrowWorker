<?php
/**
 * User: Arrow
 * Date: 2016/8/1
 * Time: 19:51
 */

namespace ArrowWorker\Driver\Db;
use ArrowWorker\Driver\Db AS db;


class Mysqli extends db
{

    //初始化数据库连接类
    static function init($config, $alias)
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

    private function connectInit($config)
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

    //连接数据库
    protected function getConnection($isMaster=false,$connectNum=0)
    {
        if(self::$config[self::$dbCurrent]['seperate']==0)
        {
            return $this -> connectMaster();
        }
        else
        {
            if($isMaster==true)
            {
                return $this -> connectMaster();
            }
            else
            {
                return $this -> connectSlave($connectNum);
            }
        }
        return false;
    }

    //检测并连接主库
    private function connectMaster()
    {
        if( !isset( self::$connPool[self::$dbCurrent]['master'] ) )
        {
            self::$connPool[self::$dbCurrent]['master'] = $this -> connectInit( self::$config[self::$dbCurrent]['master'] );
        }
        return self::$connPool[self::$dbCurrent]['master'];
    }

    //检测并连接从库
    private function connectSlave($slaveIndex=0)
    {
        $slaveCount = count(self::$config[self::$dbCurrent]['slave']);
        $slave = ( $slaveIndex==0 || $slaveIndex>=$slaveCount || $slaveIndex<0 ) ? mt_rand( 0, $slaveCount-1 ) : $slaveIndex;

        if ( !isset( self::$connPool[self::$dbCurrent]['slave'][$slave] ) )
        {
            self::$connPool[self::$dbCurrent]['slave'][$slave] = $this -> connectInit(self::$config[self::$dbCurrent]['slave'][$slave]);
        }
        return self::$connPool[self::$dbCurrent]['slave'][$slave];
    }

    //查询
    public function query($sql,$isMaster=false,$connectNum=0)
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

    //写入或更新
    public function execute($sql)
    {
        $conn = $this -> getConnection(true);
        return [
            'result'       => $conn->query($sql),
            'affectedRows' => $conn->affected_rows,
            'insertId'     => $conn->insert_id
        ];
    }

    //开始事务
    public function begin()
    {

        $this -> autocommit(false);
        $this -> getConnection(true) -> begin_transaction();
    }

    //提交事务
    public function commit()
    {
        $conn = $this -> getConnection(true);
        $conn -> commit();
        $conn -> autocommit(true);
    }

    //回滚
    public function rollback()
    {
        $conn = $this -> getConnection(true);
        $conn -> rollback();
        $conn -> autocommit(true);
    }

    //是否自动提交
    public function autocommit($flag)
    {
        $this -> getConnection(true) -> autocommit($flag);
    }

    //启动sql组合
    static function table($table)
    {
        $sqlBuilder = new SqlBuilder( self::$instance );
        return $sqlBuilder -> table($table);
    }

}
