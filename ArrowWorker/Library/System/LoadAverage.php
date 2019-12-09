<?php
namespace ArrowWorker\Library\System;

class LoadAverage
{

    private static $_return    = [
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

    public static function Get(array $diskKeyWords = ['/'] )
    {
        static::_getCpuMemoryInfo();
        foreach($diskKeyWords as $eachKeyWord)
        {
            static::_getDiskInfo($eachKeyWord);
        }
        return static::$_return;
    }

    //获取磁盘信息
    public static function _getDiskInfo(string $eachKeyWord)
    {
        $resource = popen('df -lh | grep -E "^(' . $eachKeyWord . ')"',"r");

        if( !is_resource($resource) )
            return [];

        $diskString = fread( $resource,1024);

        if( !is_string($diskString) )
            return [];

        pclose( $resource );
        $diskString = preg_replace("/\s{2,}/",' ', $diskString);  //把多个空格换成单个空格
        $diskArray = explode(' ',$diskString);

        static::$_return['disk'][] = [
            'total'   => $diskArray[1],
            'used'    => $diskArray[2],
            'left'    => $diskArray[3],
            'percent' => $diskArray[4],
            'mount'   => preg_replace("/[\r\n]/","", $diskArray[5]),
        ];
    }

    private static function _getCpuMemoryInfo()
    {
        //$loadAvgCommand = ( $this->_sysType == "centos" ) ? 'top -b -d 1 -n 2 | grep -E "^(\s+Cpu|Mem|Tasks)"' : 'top -b -d 1 -n 2 | grep -E "^(%Cpu|KiB Mem|Tasks)"';
        $sysArray  = static::Exec('top -b -d 1 -n 2 | grep -E "(Cpu|Mem|Tasks)"');

        if( count($sysArray)==1 )
        {
            return ;
        }

        $tastArray = explode(",",$sysArray[4]);
        $cpuArray  = explode(",",$sysArray[5]);
        $memArray  = explode(",",$sysArray[6]);

        //进程
        if( count($tastArray)>1 )
        {
            static::$_return['proc']['total']    = intval( static::_parseToNumber($tastArray[0]) );
            static::$_return['proc']['running']  = intval( static::_parseToNumber($tastArray[1]) );
            static::$_return['proc']['sleeping'] = intval( static::_parseToNumber($tastArray[2]) );
            static::$_return['proc']['zombie']   = intval( static::_parseToNumber($tastArray[4]) );
        }

        //CPU
        if( count($cpuArray)>1 )
        {
            static::$_return['cpu']['usrUsage'] = floatval(static::_parseToNumber($cpuArray[0]));
            static::$_return['cpu']['sysUsage'] = floatval(static::_parseToNumber($cpuArray[1]));
        }

        //内存
        if( count($memArray)>1 )
        {
            static::$_return['mem']['total'] = intval(static::_parseToNumber($memArray[0]));
            static::$_return['mem']['used']  = intval(static::_parseToNumber($memArray[1]));
        }
    }

    private static function _parseToNumber($oraString)
    {
        if( $oraString == "" )
        {
            return 0;
        }
        return (int)preg_replace("/[a-zA-Z\(\)\:\%\s]/","", $oraString);
    }

    public static function Exec(string $command) : array
    {
        $result     = [];
        exec($command,$result,$status);
        return $result;
    }

}

/*
 * 使用方法
 * $info = SysMonitor::Get(["/", "/dev/sda1"]);
*/




