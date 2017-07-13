<?php
/**
 * User: Arrow
 * Date: 2016/8/1
 * Time: 19:52
 * Modified by louis at 2017/02/03 23:58
 */

namespace ArrowWorker\Driver\Daemon;
use ArrowWorker\Driver\Daemon AS daemon;
use ArrowWorker\Driver\Daemon\ArrowThread;

class ArrowDaemon extends daemon
{
    //pid文件路径
    private static $pid_Path    = '/var/run';
    //pid文件完整路径
    private static $pid_File    = null;
    //默认pid文件名称
    private static $pid_Name    = 'ArrowWorker';
    //应用名称
    private static $App_Name    = 'ArrowWorker';
    //运行用户
    private static $user        = 'root';
    //退出标识
    private static $terminate   = false;
    //日志前缀时间时区
    private static $tipTimeZone = 'UTC';
    //任务数量
    private static $jobNum      = 0;
    //进程执行权限
    private static $umask       = 0;
    //进程日志文件
    private static $output      = '/var/log/ArrowWorker.log';
    //是否以单例模式运行
    private static $isSingle    = true;
    //是否多线程模式
    private static $isMultiThr  = false;
    //任务map
    private static $jobs        = [];
    //进程 ID map
    private static $tmpPid      = [];
    //线程池
    private static $threadMap   = [];
    //线程数
    private static $threadNum   = 6;
    //进程运行状态：开始时间、任务执行次数、结束时间
    private static $workerStat  = ['start' => null, 'count' => 0, 'end' => null];

    public function __construct($config)
    {
        parent::__construct($config);
        //设置运行日志级别
        error_reporting(self::$config['level']);

        self::$isSingle = true;
        self::$user     = isset(self::$config['user']) ? self::$config['user'] : self::$user;
        self::$pid_Name = isset(self::$config['pid'])  ? self::$config['pid']  : self::$pid_Name;
        self::$output   = isset(self::$config['log'])  ? self::$config['log']  : self::$output;
        self::$threadNum = isset(self::$config['thread'])  ? self::$config['thread']  : self::$threadNum;
        self::$App_Name = isset(self::$config['name']) ? self::$config['name'] : self::$App_Name;
        $this -> _environmentCheck();
        $this -> _daemonMake();
    }

    static function initDaemon($config)
    {
        if(!self::$daemonObj)
        {
            self::$daemonObj = new self($config);
        }
        return self::$daemonObj;
    }

    private function _environmentCheck()
    {
        if (php_sapi_name() != "cli")
        {
            die("only run in command line mode\n");
        }

        if ( ! function_exists('pcntl_signal_dispatch'))
        {
            declare(ticks = 10);
        }

        if ( ! function_exists('pcntl_signal'))
        {
            $message = 'php environment do not support pcntl_signal';
            $this -> _logWrite($message);
            throw new Exception($message);
        }

        $fl = fopen(self::$output, 'w') or die("cannot create log file");
              fclose($fl);

        if (function_exists('gc_enable'))
        {
            gc_enable();
        }
        
        self::$isMultiThr = extension_loaded('pthreads');

    }

    private function _daemonMake()
    {
        
        set_time_limit(0);

        if (self::$isSingle == true)
        {
            self::$pid_File = self::$pid_Path . "/" . self::$pid_Name . ".pid";
            $this -> _checkPidFile();
        }

        umask(self::$umask);

        if (pcntl_fork() != 0)
        {
            exit();
        }

        posix_setsid();

        if (pcntl_fork() != 0)
        {
            exit();
        }

        chdir("/");
        $proStartTime = date("Y-m-d H:i:s");
        $this -> _userSet(self::$user) or die("Setting process user failed！");
        $this -> _resetStd();
        $this -> _setProcessName("ArrowWorker V1.3 --By Louis --started at ".$proStartTime);
        if (self::$isSingle==true)
        {
            $this -> _createPidFile();
        }

    }

    private function _resetStd()
    {
        global $STDOUT, $STDERR;
        $handle = fopen(self::$output, "a");
        if ($handle)
        {
            unset($handle);
            fclose(STDOUT);
            fclose(STDERR);
            $STDOUT = fopen(self::$output, 'a');
            $STDERR = fopen(self::$output, 'a');
        }
        else
        {
            $this -> _logWrite("can not open stdoutFile");       
        }
    }

    private function _createPidFile()
    {

        if (!is_dir(self::$pid_Path))
        {
            mkdir(self::$pid_Path);
        }

        $fp = fopen(self::$pid_File, 'w') or die("cannot create pid file");
        fwrite($fp, posix_getpid());
        fclose($fp);

        $this -> _logWrite("create pid file " . self::$pid_File);
    }

    private function _checkPidFile()
    {

        if (!file_exists(self::$pid_File))
        {
            return true;
        }

        $pid = intval(file_get_contents(self::$pid_File));

        if ($pid > 0 && posix_kill($pid, 0))
        {
            $this -> _logWrite("Daemon process is already started");
        }
        else
        {
            $this -> _logWrite("Daemon process ended abnormally , Check your program." . self::$pid_File);
        }

        exit(1);

    }

    private function _setSignalHandler($type = 'parentsQuit',$lifecycle=0)
    {
        switch($type)
        {
            case 'workerHandler':
                pcntl_signal(SIGTERM, array(__CLASS__, "signalHandler"),false);
                pcntl_signal(SIGALRM, array(__CLASS__, "signalHandler"),false);
                pcntl_alarm($lifecycle);
                break;
            default:
                pcntl_signal(SIGCHLD, array(__CLASS__, "signalHandler"),false);
                pcntl_signal(SIGTERM, array(__CLASS__, "signalHandler"),false);
                pcntl_signal(SIGINT,  array(__CLASS__, "signalHandler"),false);
                pcntl_signal(SIGQUIT, array(__CLASS__, "signalHandler"),false);
        }
    }

    public function signalHandler($signal)
    {
        switch($signal)
        {
            case SIGUSR1:
            case SIGALRM:
                self::$terminate = true;
                break;
            case SIGTERM:
            case SIGHUP:
            case SIGINT:
            case SIGQUIT:
                self::$terminate = true;
                $this -> _exitWorkers();
                break;
            default:
                return false;
        }

    }

    private function _userSet($name)
    {

        $result = false;
        if (empty($name))
        {
            return true;
        }

        $user = posix_getpwnam($name);

        if ($user)
        {
            $uid = $user['uid'];
            $gid = $user['gid'];
            $result = posix_setuid($uid);
            posix_setgid($gid);
        }
        return $result;

    }

    private function _setProcessName($proName)
    {
        $proName = self::$App_Name.' -- '.$proName;
        if(function_exists('cli_set_process_title'))
        {
            @cli_set_process_title($proName);
        }
        elseif(extension_loaded('proctitle')&&function_exists('setproctitle'))
        {
            @setproctitle($proName);
        }
    }

    public function start()
    {

        self::$jobNum = count(self::$jobs,0);

        if(self::$jobNum == 0)
        {
            $this -> _logWrite("please add some jobs");
            $this -> _clearArrowInfo();
        }
        $this -> _setSignalHandler('monitorHandler');
        $this -> _forkWorkers();
        $this -> _startMonitor();
        $this -> _clearArrowInfo();
    }

    private function _exitWorkers()
    {
        foreach(self::$tmpPid as $key => $val)
        {
            $result = posix_kill($key,SIGUSR1);
            if(!$result)
            {
                 posix_kill($key,SIGUSR1);
            }
        }
    }

    private function _startMonitor()
    {
        while (1)
        {
            if(self::$terminate)
            {
                break;
            }

            pcntl_signal_dispatch();

            $status = 0;
            //returns the process ID of the child which exited, -1 on error or zero if WNOHANG was provided as an option (on wait3-available systems) and no child was available
            $pid    = pcntl_wait($status, WUNTRACED);
            pcntl_signal_dispatch();

            if ($pid > 0) 
            {
                $taskGroupId = self::$tmpPid[$pid];
                self::$jobs[$taskGroupId]['pidCount']--;
                unset(self::$tmpPid[$pid]);
                $this -> _forkOneWork($taskGroupId);
                $this -> _logWrite("Task process(".self::$jobs[$taskGroupId]["processName"]."-".$pid.":".$status.") exited.");
            }
        }
    }
    

    private function _forkWorkers()
    {
        for($i = 0; $i<self::$jobNum; $i++)
        {   
            while(self::$jobs[$i]['pidCount'] < self::$jobs[$i]['concurrency'])
            {
                $this -> _forkOneWork($i);
            }
            usleep(10000);
        }
    }

    private function _forkOneWork($taskGroupId)
    {
        $pid = -1;

        $pid = pcntl_fork();
               
        if($pid > 0)
        {   
            self::$jobs[$taskGroupId]['pidCount']++;
            self::$tmpPid[$pid] = $taskGroupId;
        }
        elseif($pid==0)
        {   
            $this -> _runWorker($taskGroupId,self::$jobs[$taskGroupId]['lifecycle']);
        }
        else
        {   
            sleep(2);
        }
    }

    private function _runWorker($index,$lifecycle)
    {
        $this -> _setSignalHandler('workerHandler',$lifecycle);
        $this -> _setProcessName(self::$jobs[$index]['processName']);
        self::$workerStat['start'] = time();
        if( self::$isMultiThr )
        {
            $this -> _threadRunTask( $index );
        }
        else
        {
            $this -> _processRunTask( $index );
        }
    }

    //进程执行任务
    private function _processRunTask($index)
    {
        while( 1 )
        {
            if( self::$terminate )
            {
                self::$workerStat['end'] = time();
                $proWorkerTimeSum  = self::$workerStat['end'] - self::$workerStat['start'];
                $this -> _logWrite( self::$jobs[$index]['processName'].' finished '.self::$workerStat['count'].' times of its work in '.$proWorkerTimeSum.' seconds.' );
                exit(0);
            }

            pcntl_signal_dispatch();
            if( isset( self::$jobs[$index]['argv'] ) )
            {
                call_user_func_array( self::$jobs[$index]['function'], self::$jobs[$index]['argv'] );
            }
            else
            {
                call_user_func( self::$jobs[$index]['function'] );
            }

            self::$workerStat['count']++;
        }
    }

    //线程执行任务
    private function _threadRunTask($index)
    {
        //创建线程
        for( $i = 1; $i <= self::$threadNum; $i++  )
        {
            self::$threadMap[] = new ArrowThread( self::$jobs[$index]['processName'].'_thread_'.$i );
        }

        //启动线程
        foreach( self::$threadMap as $workerThread )
        {
            $workerThread -> start();
        }

        //循环给线程分发任务
        while( 1 )
        {
            if( self::$terminate )
            {
                //退出所有线程
                foreach( self::$threadMap as $key => $workerThread )
                {
                    $workerThread -> endThread();
                    $workerThread -> join();
                    unset( self::$threadMap[$key] );
                }   
                self::$workerStat['end'] = time();
                $proWorkerTimeSum  = self::$workerStat['end'] - self::$workerStat['start'];
                $this -> _logWrite(self::$jobs[$index]['processName'].' finished '.self::$workerStat['count'].' times of its work in '.$proWorkerTimeSum.' seconds.');
                exit(0);
            }

            pcntl_signal_dispatch();

            foreach( self::$threadMap as $workerThread )
            {
                //线程空闲
                if( !$workerThread -> hasTask )
                {
                    $workerThread -> pushTask( self::$jobs[$index] );
                    self::$workerStat['count']++;
                }
            }
            usleep(20);
        }
    }

    private function _clearArrowInfo()
    {
        if (file_exists(self::$pid_File))
        {
            unlink(self::$pid_File);
            $this -> _logWrite("delete pid file " . self::$pid_File);
        }
        $this -> _logWrite("ArrowWorker exits.");
        exit(0);

    }

    public function addTask($job=array())
    {
        
        if(!isset($job['function'])||empty($job['function']))
        {
            $this -> _logWrite("Task is needed,Sir");
            exit(0);
        }

        $job['pidCount']    = 0;
        $job['lifecycle']   = (isset($job['lifecycle']) && is_int($job['lifecycle']))   ? $job['lifecycle']   : 0 ;
        $job['concurrency'] = (isset($job['concurrency']) && is_int($job['concurrency'])) ? $job['concurrency'] : 0 ;
        $job['processName'] = (!isset($job['proName'])||empty($job['proName'])) ? 'unnamed process' : $job['proName'];

        self::$jobs[] = $job;
    }

    private  function _logWrite($message)
    {
        date_default_timezone_set(self::$tipTimeZone);
        @printf("%s\t%d\t%d\t%s\n", date("c"), posix_getpid(), posix_getppid(), $message);
    }

}
