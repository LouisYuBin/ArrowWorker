<?php
namespace App\Classes;
/**
 * Created by PhpStorm.
 * 系统负载监控工具
 * User: louis
 * Date: 17-8-24
 * Time: 下午4:40
 */
class SystemLoad
{

    private $_return    = [
        'cpu' => [
            'usrUsage' => '',
            'sysUsage' => '',
        ],
        'mem' => [
            'used'  => '',
            'total' => ''
        ],
        'disk' => [],
        'proc' => [
            'total'    => 0,
            'running'  => 0,
            'sleeping' => 0,
            'zombie'   => 0,
        ]
    ];
    private $_diskInfo = [
        'mount'   => '',
        'total'   => '',
        'used'    => '',
        'left'    => '',
        'percent' => '',
    ];
    private $_resource;

    public function get( $diskKeyWords = [] )
    {
        $this -> _getCpuMemoryInfo();
        foreach($diskKeyWords as $eachKeyWord)
        {
            $this -> _getDiskInfo($eachKeyWord);
        }
        return $this -> _return;
    }

    //获取磁盘信息
    private function _getDiskInfo($eachKeyWord)
    {
        $this->_resource = popen('df -lh | grep -E "^(' . $eachKeyWord . ')"',"r");

        if( !is_resource($this->_resource) )
            return ;

        $diskString = fread( $this->_resource,1024);

        if( !is_string($diskString) )
            return ;

        pclose( $this->_resource );
        $diskString = preg_replace("/\s{2,}/",' ', $diskString);  //把多个空格换成单个空格
        $diskArray = explode(' ',$diskString);

        $this->_diskInfo["total"]   = $diskArray[1];
        $this->_diskInfo["used"]    = $diskArray[2];
        $this->_diskInfo["left"]    = $diskArray[3];
        $this->_diskInfo["percent"] = $diskArray[4];
        $this->_diskInfo["mount"]   = preg_replace("/[\r\n]/","", $diskArray[5]);

        $this->_return["disk"][]    = $this->_diskInfo;
    }

    private function _getCpuMemoryInfo()
    {
        //$loadAvgCommand = ( $this->_sysType == "centos" ) ? 'top -b -d 1 -n 2 | grep -E "^(\s+Cpu|Mem|Tasks)"' : 'top -b -d 1 -n 2 | grep -E "^(%Cpu|KiB Mem|Tasks)"';
        $loadAvgCommand =  'top -b -d 1 -n 2 | grep -E "(Cpu|Mem|Tasks)"';

        //获取某一时刻系统cpu和内存使用情况
        $this -> _resource = popen($loadAvgCommand,"r");

        if( !is_resource($this->_resource) )
            return ;

        $sysLoad = "";
        while(!feof($this->_resource)){
            $sysLoad .= fread($this->_resource,1024);
        }
        pclose($this -> _resource);

        var_dump($sysLoad);

        $sysArray  = explode("\n",$sysLoad);

        if( count($sysArray)==1 )
            return ;

        $tastArray = explode(",",$sysArray[4]);
        $cpuArray  = explode(",",$sysArray[5]);
        $memArray  = explode(",",$sysArray[6]);

        //进程
        if( count($tastArray)>1 )
        {
            $this->_return['proc']['total']    = intval( $this->_parseToNumber($tastArray[0]) );
            $this->_return['proc']['running']  = intval( $this->_parseToNumber($tastArray[1]) );
            $this->_return['proc']['sleeping'] = intval( $this->_parseToNumber($tastArray[2]) );
            $this->_return['proc']['zombie']   = intval( $this->_parseToNumber($tastArray[4]) );
        }

        //CPU
        if( count($cpuArray)>1 )
        {
            $this->_return['cpu']['usrUsage'] = floatval($this -> _parseToNumber($cpuArray[0]));
            $this->_return['cpu']['sysUsage'] = floatval($this -> _parseToNumber($cpuArray[1]));
        }

        //内存
        if( count($memArray)>1 )
        {
            $this->_return['mem']['total'] = intval($this -> _parseToNumber($memArray[0]));
            $this->_return['mem']['used']  = intval($this -> _parseToNumber($memArray[1]));
        }
    }

    private function _parseToNumber($oraString)
    {
        if( $oraString == "" )
        {
            return 0;
        }
        return preg_replace("/[a-zA-Z\(\)\:\%\s]/","", $oraString);
    }

}
/*
使用方法
$load = new SystemLoad();
var_dump( $load->get(["/", "/dev/sda1"]) );
*/