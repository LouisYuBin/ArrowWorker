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
    static function init($config)
    {
        if(!self::$instance)
        {
            self::$instance = new self($config);
        }

        //存储配置
        if ( !isset( self::$config[$config['alias']] ) )
        {
            self::$config[$config['alias']] = $config['alias'];
        }

        //设置当前
        self::$dbCurrent = $config['alias'];

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
        $Conn -> query("set names '".self::$config['charset']."'");
        return $Conn;
    }

    //连接数据库
    protected function connect($isMaster=false,$connectNum=0)
    {
        if(self::$config[self::$dbCurrent]['seperate']==0)
        {
            $this -> connectMaster();
        }
        else
        {
            if($isMaster==true)
            {
                $this -> connectMaster();
            }
            else
            {
                $this -> connectSlave($connectNum);
            }
        }
    }

    //检测并连接主库
    private function connectMaster()
    {
        if( !isset( self::$dbConnection[self::$dbCurrent]['master'] ) )
        {
            self::$dbConnection[self::$dbCurrent]['master'] = $this -> connectInit( self::$config[self::$dbCurrent]['master'] );
        }
        self::$dbConn = self::$dbConnection[self::$dbCurrent]['master'];
    }

    //检测并连接从库
    private function connectSlave($slaveIndex=0)
    {
        $slaveCount = count(self::$config[self::$dbCurrent]['slave']);
        $slave = ( $slaveIndex==0 || $slaveIndex>=$slaveCount || $slaveIndex<0 ) ? mt_rand( 0, $slaveCount-1 ) : $slaveIndex;

        if ( !isset( self::$dbConnection[self::$dbCurrent]['slave'][$slave] ) )
        {
            self::$dbConnection[self::$dbCurrent]['slave'][$slave] = $this -> connectInit(self::$config[self::$dbCurrent]['slave'][$slave]);
        }
        self::$dbConn = self::$dbConnection[self::$dbCurrent]['slave'][$slave];
    }

    //查询
    public function query($sql,$isMaster=false,$connectNum=0)
    {
        $this -> connect($isMaster,$connectNum);
        $result = self::$dbConn -> query($sql);
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
        $this -> connect(true);
        return [
            'result'       => self::$dbConn->query($sql),
            'affectedRows' => self::$dbConn->affected_rows,
            'insertId'     => self::$dbConn->insert_id
        ];
    }

    //开始事务
    public function begin()
    {
        $this -> connect(true);
        $this -> autocommit(false);
        self::$dbConn -> begin_transaction();
    }

    //提交事务
    public function commit()
    {
        $this -> connect(true);
        self::$dbConn -> commit();
        $this -> autocommit(true);
    }

    //回滚
    public function rollback()
    {
        $this -> connect(true);
        self::$dbConn -> rollback();
        $this -> autocommit(true);
    }

    //是否自动提交
    public function autocommit($flag)
    {
        $this -> connect(true);
        self::$dbConn -> autocommit($flag);
    }

    //启动sql组合
    static function table($table)
    {
        $sqlBuilder = new SqlBuilder( self::$dbObj );
        return $sqlBuilder -> table($table);
    }

}
