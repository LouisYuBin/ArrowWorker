<?php
/*
 * Author：Louis
 * Date：2015-11-17
 * update：2016-06-12
 * Email：1210077683@qq.com
 * */
class ArrowWorder
{
    // Store path of pid file
    private $pid_Path = '/tmp';
    // path for pid file
    private $pid_File  = '';
    // Name of pid file
    private $pid_Name  = 'ArrowWorker';
    // user for the current process
    private $user       = 'root';
    // sign of exit for the monitor process
    private $terminate  = false;
    // time Zone
    private $tipTimeZone     = 'UTC';
    // worker process number
    private $jobNum     = 0;
    // Running mask
    private $umask      = 0;
    // standard output file
    private $output     = '/dev/null';
    // Single model or not
    private $isSingle   = true;
    // Job array
    private $jobs   = array();
    // Process name
    private $proName = 'ArrowWorker';
    // Map between job and process
    private $tmpPid = array();

    public function __construct($isSingle = true,$user = 'root',$pidName = '')
    {
        $this -> isSingle = $isSingle;
        $this -> user     = $user;
        if($pidName != '')
        {
            $this -> pid_Name = $pidName;
        }

        $this -> checkEnvironment();
    }

    //Check program start model and functions needed
    private function checkEnvironment()
    {

        //Check if the JobRunner is starting in command line
        if (php_sapi_name() != "cli")
        {
            die("only run in command line mode\n");
        }
            
        //Check the exists of function pcntl_signal_dispatch
        if ( ! function_exists('pcntl_signal_dispatch'))
        {
            declare(ticks = 10);
        }

        //Check the exists of function pcntl_signal
        if ( ! function_exists('pcntl_signal'))
        {
            throw new Exception('php do not support pcntl_signal');
        }

        //Register the signal handler for parent process's exit
        $this ->setSignalHandler('parentsQuit');

        if (function_exists('gc_enable'))
        {
            gc_enable();
        }

    }

    // daemonize process
    public function daemonize(){

        set_time_limit(0);

        if ($this->isSingle == true)
        {
            $this -> pid_File = $this -> pid_Path . "/" . $this->pid_Name . ".pid";
            $this -> checkPidfile();
        }

        umask($this->umask);

        if (pcntl_fork() != 0)
        {
            exit();
        }
        
        //Set the new session group
        posix_setsid();

        if (pcntl_fork() != 0)
        {
            exit();
        }


        //Change the work directory for the JobRunner
        chdir("/");

        $this -> redirectStd();

        $this -> userSet($this->user) or die("Setting process user failed！");

        if ($this->isSingle==true)
        {
            $this -> createPidfile();
            $this -> setProcessName($this->proName);
        }

    }

    /*
     * Redirect standard input 、output and error
     * */
    private function redirectStd()
    {
        global $stdin, $stdout, $stderr;
        $handle = fopen(self::$stdoutFile, "a");
        if($handle)
        {
            fclose(STDIN);
            fclose(STDOUT);
            fclose(STDERR);

            $stdin  = fopen($this->output, 'r');
            $stdout = fopen($this->output, 'a');
            $stderr = fopen($this->output, 'a');
        }
        else
        {
            throw new \Exception('Can not open standard file '.$this->output);
        }

    }

    //Create pid file for the JobRunner
    private function createPidfile()
    {
        if (!is_dir($this->pid_Path))
        {
            mkdir($this->pid_Path);
        }

        //Save the process id to the pid file
        $fp = fopen($this->pid_File, 'w') or die("cannot create pid file");
              fwrite($fp, posix_getpid());
              fclose($fp);
        $this -> logWrite("create pid file " . $this->pid_File);
    }

    //Set the JobRunner name
    private function setProcessName($proName)
    {
        if(function_exists('cli_set_process_title'))
        {
            @cli_set_process_title($proName);
        }
        elseif(extension_loaded('proctitle')&&function_exists('setproctitle'))
        {
                @setproctitle($proName);
        }
    }

    //Check the pid file of the JobRunner
    public function checkPidfile()
    {

        if (!file_exists($this-> pid_File))
        {
            return true;
        }

        $pid = intval(file_get_contents($this->pid_File));

        //Send a empty signal to process to check if the process is exists
        if ($pid > 0 && posix_kill($pid, 0))
        {
            $this -> logWrite("Daemon process is already started");
        }
        else
        {
            $this -> logWrite("Daemon process ended abnormally , Check your program " . $this->pid_File);
        }

        exit(1);

    }

    //Signal handle function
    public function setSignalHandler($type = 'parentsQuit')
    {
        switch($type)
        {
            case 'workerHandler':
                pcntl_signal(SIGTERM, SIG_DFL);
                pcntl_signal(SIGCHLD, SIG_DFL);
                break;
            case 'startHandler':
                pcntl_signal(SIGCHLD, array(__CLASS__, "signalHandler"),false);
                break;
            default:
                //Handle the exit signal of parent process
                pcntl_signal(SIGTERM, array(__CLASS__, "signalHandler"),false);
                pcntl_signal(SIGINT, array(__CLASS__, "signalHandler"),false);
                pcntl_signal(SIGQUIT, array(__CLASS__, "signalHandler"),false);
        }
    }

    public function signalHandler($signal)
    {
        switch($signal)
        {
            // Main process will catch the signal while the child process exit
            case SIGCHLD:
                // pcntl_waitpid return the id of the child process exited，return -1 when abnormally
                while(($pid=pcntl_waitpid(-1, $status, WNOHANG)) > 0)
                {
                    // get the id of the process exited
                    $jobNum = $this -> tmpPid['a'.$pid];
                    // reset the worker exited to unhandled status
                    $this->jobs[$jobNum]['pid'] = 0;
                    //unset the array element
                    unset($this -> tmpPid['a'.$pid]);
                }
                break;

            //进程退出时将进程终止标志置位ｔｒｕｅ，任务分发（监控）进程检测到终止为ｔｒｕｅ时退出
            case SIGTERM:
            //（上）上面为进程结束信号
            case SIGHUP:
            //（上）进程与终端连接结束（进程脱离终端，由ctrl+/发出）
            case SIGINT:
            //（上）进程终止信号，由（ctrl+c发出）
            case SIGQUIT:
            //（上）程序终止信号(kill send it)，程序发生错误退出信号
                $this->terminate = true;
                break;
            default:
                return false;
        }

    }

    // set user and group for the process
    public function userSet($name)
    {

        $result = false;
        if (empty($name))
        {
            return true;
        }

        //get information of user specified
        $user = posix_getpwnam($name);

        if ($user)
        {
            $uid = $user['uid'];
            $gid = $user['gid'];
            //set user for the process
            $result = posix_setuid($uid);
            //set user group for the process
            posix_setgid($gid);
        }
        return $result;

    }

    // start daemon
    public function start($count=1){

        $this -> logWrite("Daemon process is working");

        //设置工作进程退出时，父（监控）进程处理
        $this ->setSignalHandler('startHandler');

        //the number of the job added
        $this ->jobNum = count($this->jobs,0);

        // exit if no job is added
        if($this ->jobNum == 0)
        {
            $this -> logWrite("please add some jobs");
            $this -> mainQuit();
        }

        //monitor the worker
        while (true)
        {
            //use the system signal handler to handle the process signal if ...
            if (function_exists('pcntl_signal_dispatch'))
            {
                pcntl_signal_dispatch();
            }

            //exit the monitor process if exit signal is received
            if ($this->terminate)
            {
                break;
            }

            //loop to fork the worker process
            for($i = 0; $i<$this ->jobNum; $i++)
            {
                if($this -> jobs[$i]['pid']==0)
                {
                    $pid = -1;
                    $pid = pcntl_fork();

                    // 创建工作进程成功，（父进程）任务执行状态置为1（执行）
                    if($pid > 0)
                    {
                        $this -> jobs[$i]['pid']    = $pid;
                        //将工作进程pid和任务对应起来
                        $this -> tmpPid['a'.$pid]   = $i;
                    }
                    // 工作进程执行任务
                    elseif($pid==0)
                    {
                        // 设置工作进程退出信号处理
                        $this ->setSignalHandler('workerHandler');
			            // 设置工作进程名称
			            $this ->setProcessName($this ->proName . '->' . $this->jobs[$i]['function']);

                        if(empty($this->jobs[$i]['argv']))
                        {
                            call_user_func($this->jobs[$i]['function'],$this->jobs[$i]['argv']);
                        }
                        else
                        {
                            call_user_func($this->jobs[$i]['function']);
                        }
                        //任务执行完毕后等待一秒，然后退出
                        usleep(500000);
                        exit();

                    }
                    // 创建工作进程失败
                    else
                    {
                        sleep(2);
                    }
                }
                usleep(100000);
            }
        }

        // 退出工作进程
        $this -> mainQuit();

    }

    //monitor process exit
    public function mainQuit()
    {
        if (file_exists($this->pid_File))
        {
            unlink($this->pid_File);
            $this -> logWrite("delete pid file " . $this->pid_File);
        }
        $this -> logWrite("Daemon process is already exits.");
        posix_kill(0, SIGKILL);
        exit(0);

    }

    // add job
    public function addJobs($jobs=array())
    {
        // 任务函数所带参数
        if(!isset($jobs['argv'])||empty($jobs['argv']))
            $jobs['argv']="";

        // 任务运行次数
        if(!isset($jobs['runtime'])||empty($jobs['runtime']))
            $jobs['runtime'] = 1;

        // 任务函数名称
        if(!isset($jobs['function'])||empty($jobs['function']))
        {
            $this -> logWrite("Function is needed");
            exit(0);
        }

        // 任务初始工作进程：0
        $jobs['pid']    = 0 ;

        $this->jobs[] = $jobs;

    }

    private  function logWrite($message)
    {
        date_default_timezone_set($this -> tipTimeZone);
        printf("%s\t%d\t%d\t%s\n", date("c"), posix_getpid(), posix_getppid(), $message);
    }

}

function ScanScreenIds($cut,$count)
{
    $redis = new \Redis();
    $redis -> connect('127.0.0.1',6379);
    $redis -> set('s1','first'.mt_rand(1,1000));
    sleep(1);
}

function ScanSceenStatus()
{
    $redis = new \Redis();
    $redis -> connect('127.0.0.1',6379);
    $redis -> set('s2','second'.mt_rand(1,1000));
    sleep(1);
}

function tmpfunction()
{
    sleep(10);
}

function tmplog($msg)
{
    $redis = new \Redis();
    $redis -> connect('127.0.0.1',6379);
    $redis -> set('tip',$msg);
}


$daemon =  new ArrowWorder();
$daemon -> daemonize();
//$daemon -> addJobs(['function' => 'ScanScreenIds','argv' => array(100,2)]);
$daemon -> addJobs(['function' => 'ScanSceenStatus']);
$daemon -> addJobs(['function' => 'tmpfunction']);

$daemon -> start(1);
