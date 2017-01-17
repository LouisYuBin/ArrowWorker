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
    static function initDb($config)
    {
        if(!self::$dbObj)
        {
            self::$dbObj = new self($config);
        }
        return self::$dbObj;
    }

    private function connectDb($config)
    {
        //建立连接
        @self::$dbConn = new \mysqli($config['host'],$config['userName'],$config['password'],$config['dbName'],$config['port']);
        //捕捉错误
        if(self::$dbConn->connect_errno)
        {
            exit(self::$dbConn->connect_error);
        }
        //初始化字符集
        self::$dbConn ->query("set names '".self::$config['charset']."'");
    }

    //连接数据库
    protected function connect($isMaster=false,$connectNum=0)
    {
        if(self::$config['seperate']==0)
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
                $this ->connectSlave($connectNum);
            }
        }
    }

    //检测并连接主库
    private function connectMaster()
    {
        if(isset(self::$dbConnection['master']))
        {
            self::$dbConn = self::$dbConnection['master'];
        }
        else
        {
            $this -> connectDb(self::$config['master']);
            self::$dbConnection['master'] = self::$dbConn;
        }
    }

    //检测并连接从库
    private function connectSlave($connectNum=0)
    {
        $slaveNum = count(self::$config['slave']);
        if($slaveNum==1)
        {
            $this -> connectDb(self::$config['slave'][0]);
            self::$dbConnection['slave'][0] = self::$dbConn;
        }
        else
        {
            $slave = $connectNum;
            if($connectNum==0)
            {
                //查询随机从库
                $slave = mt_rand(0,$slaveNum-1);
            }

            if(isset(self::$dbConnection['slave'][$slave]))
            {
                self::$dbConn = self::$dbConnection['slave'][$slave];
            }
            else
            {
                $this -> connectDb(self::$config['slave'][$slave]);
                self::$dbConnection['slave'][$slave] = self::$dbConn;
            }
        }
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
        $result = self::$dbConn -> query($sql);
        //to be update
        $return = ($result==false) ? false : self::$dbConn->affected_rows;
        return $return;
    }

    //写入id
    public function insert_id()
    {
        $this -> connect(true);
        $result = self::$dbConn->insert_id;
        //to be update
        $return = ($result==0) ? false : $result;
        return $return;
    }

    //开始事务
    public function begin()
    {
        $this -> autocommit(false);
        self::$dbConnection['master'] -> begin_transaction();
    }

    //提交事务
    public function commit()
    {
        self::$dbConnection['master'] -> commit();
        $this -> autocommit(true);
    }

    //回滚
    public function rollback()
    {
        self::$dbConnection['master'] -> rollback();
        $this -> autocommit(true);
    }

    //是否自动提交
    public function autocommit($flag)
    {
        self::$dbConnection['master'] -> autocommit($flag);
    }

}
