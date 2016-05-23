<?php
/*
 * Author：Louis
 * Date：2015-11-17
 * update：2016-05-23
 * Email：1210077683@qq.com
 * */
class daemonLouis
{
    // 进程pid文件存储位置
    private $pid_Path = '/tmp';
    // pid文件完整路径
    private $pid_File  = '';
    // pid文件名称
    private $pid_Name  = 'jobRunner';
    // 运行用户
    private $user       = 'root';
    // 是否终止运行
    private $terminate  = false;
    // 提醒时间设置
    private $tipTimeZone     = 'UTC';
    // 工作进程个数
    private $jobNum     = 0;
    // 运行权限
    private $umask      = 0;
    // 日志输出目录
    private $output     = '/dev/null';
    // 是否以单例模式运行
    private $isSingle   = true;
    // 任务数组
    private $jobs   = array();
    // 进程名称
    private $proName = 'ArrowRunner';
    // 任务与进程映射表
    private $tmpPid = array();

    public function __construct($isSingle = true,$user = 'root',$pidName = '')
    {
        $this -> isSingle = $isSingle;
        $this -> user     = $user;
        if($pidName != '')
        {
            $this -> pid_Name = $pidName;
        }

        //相关环境检查函数
        $this -> environmentCheck();
    }

    private function environmentCheck()
    {

        //是否通过命令行启动
        if (php_sapi_name() != "cli")
        {
            die("only run in command line mode\n");
        }
            
        //信号检测机制函数是否存在
        if ( ! function_exists('pcntl_signal_dispatch'))
        {
            //不存在时，每执行１０条ｏｐｃｏｄｅ检查一次信号
            declare(ticks = 10);
        }
            

        //检测注册信号处理函数是否存在
        if ( ! function_exists('pcntl_signal'))
        {
            $message = 'php environment do not support pcntl_signal';
            $this -> logWrite($message);
            throw new Exception($message);
        }

        //注册进程退出信号处理函数
        $this ->setSignalHandler('parentsQuit');

        if (function_exists('gc_enable'))
        {
            gc_enable();
        }

    }

    public function daemonMake(){

        global $stdin, $stdout, $stderr;
        set_time_limit(0);

        if ($this->isSingle == true)
        {
            $this -> pid_File = $this -> pid_Path . "/" . $this->pid_Name . ".pid";
            //检查进程ｐｉｄ文件
            $this -> checkPidfile();
        }

        //设定程序运行权限（相减关系）
        umask($this->umask);

        if (pcntl_fork() != 0)
        {
            exit();
        }
        
        //设置会话组长
        posix_setsid();

        if (pcntl_fork() != 0)
        {
            exit();
        }


        //切换进程的工作目录
        chdir("/");

        $this -> userSet($this->user) or die("Setting process user failed！");

        //关闭进程开启时ｐｈｐ默认打开的文件
        fclose(STDIN);
        fclose(STDOUT);
        fclose(STDERR);

        $stdin  = fopen($this->output, 'r');
        $stdout = fopen($this->output, 'a');
        $stderr = fopen($this->output, 'a');

        if ($this->isSingle==true)
        {
            $this -> createPidfile();
            $this -> setProcessName($this->proName);
            
        }

    }

    //创建进程pid文件
    private function createPidfile()
    {

        //Pid 文件存储路径不存在则创建
        if (!is_dir($this->pid_Path))
        {
            mkdir($this->pid_Path);
        }

        //将进程ｉｄ文件保存在相应的ｐｉｄ文件中
        $fp = fopen($this->pid_File, 'w') or die("cannot create pid file");
        fwrite($fp, posix_getpid());
        fclose($fp);
        $this -> logWrite("create pid file " . $this->pid_File);
    }

    //设置进程名称
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

    //检测进程ｐｉｄ文件是否存在
    public function checkPidfile()
    {

        if (!file_exists($this-> pid_File))
        {
            return true;
        }

        $pid = intval(file_get_contents($this->pid_File));

        if ($pid > 0 && posix_kill($pid, 0))
        {
            $this -> logWrite("Daemon process is already started");
        }
        else
        {
            $this -> logWrite("Daemon process ended abnormally , Check your program." . $this->pid_File);
        }

        exit(1);

    }

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
                //进程退出信号处理函数
                pcntl_signal(SIGTERM, array(__CLASS__, "signalHandler"),false);
                pcntl_signal(SIGINT, array(__CLASS__, "signalHandler"),false);
                pcntl_signal(SIGQUIT, array(__CLASS__, "signalHandler"),false);
        }
    }

    public function signalHandler($signal)
    {
        switch($signal)
        {
            // 子进程结束时，父进程收到此信号
            case SIGCHLD:
                // pcntl_waitpid 返回退出的子进程的进程号，错误时返回-1
                while(($pid=pcntl_waitpid(-1, $status, WNOHANG)) > 0)
                {
                    // 取得退出子进程最低的任务编号
                    $jobNum = $this -> tmpPid['a'.$pid];
                    // 将工作进程对应的任务的状态置为待执行状态
                    $this->jobs[$jobNum]['pid'] = 0;
                    //消除上一次对应数据
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
            //（上）程序终止信号，程序发生错误退出信号
                $this->terminate = true;
                break;
            default:
                return false;
        }

    }

    // 设置运行进程的用户
    public function userSet($name)
    {

        $result = false;
        if (empty($name))
        {
            return true;
        }

        //根据用户名获取对应的用户信息
        $user = posix_getpwnam($name);

        if ($user)
        {
            $uid = $user['uid'];
            $gid = $user['gid'];
            //设置当前进程运行用户的ｉｄ
            $result = posix_setuid($uid);
            //设置当前用户运行的用户组ｉｄ
            posix_setgid($gid);
        }
        return $result;

    }

    // 开始执行任务
    public function start($count=1){

        $this -> logWrite("Daemon process is working");

        //设置工作进程退出时，父（监控）进程处理
        $this ->setSignalHandler('startHandler');

        //需要执行的任务数
        $this ->jobNum = count($this->jobs,0);

        // 没有任务时提醒添加任务并退出
        if($this ->jobNum == 0)
        {
            $this -> logWrite("please add some jobs");
            $this -> mainQuit();
        }

        //进入任务分发和监控
        while (true)
        {
            //如果pcntl_signal_dispatch函数可用，则使用等待信号处理器进行信号监测
            if (function_exists('pcntl_signal_dispatch'))
            {
                pcntl_signal_dispatch();
            }

            //如果退出表示为true则退出
            if ($this->terminate)
            {
                break;
            }

            //循环读取任务并执行
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

    //主（监控）进程退出
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

    // 添加任务
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


$daemon =  new daemonLouis();
$daemon -> daemonMake();
//$daemon -> addJobs(['function' => 'ScanScreenIds','argv' => array(100,2)]);
$daemon -> addJobs(['function' => 'ScanSceenStatus']);
$daemon -> addJobs(['function' => 'tmpfunction']);

$daemon -> start(1);
