<?php
/**
 * User: louis
 * Time: 18-6-7 下午2:38
 */

namespace ArrowWorker;


class Daemon
{

    /**
     * 运行用户
     * @var string
     */
    private static $user = 'root';

    /**
     * 需要去除的进程执行权限
     * @var int
     */
    private static $umask = 0;

    /**
     * 进程输出重定向文件
     * @var string
     */
    private static $output = 'ArrowWorker';


    /**
     * pid文件路径
     * @var string
     */
    private static $pidDir = APP_PATH.DIRECTORY_SEPARATOR.APP_RUNTIME_DIR.'/Pid/';
    
    private static $pidName = 'ArrowWorker';

    private static $tipTimeZone='UTC';

    private static $errorLevel = 30719;

    private static $appName = 'ArrowWorker demo';
    

    /**
     * ArrowDaemon constructor.
     * @param array $config
     */
    public function __construct($config)
    {
        date_default_timezone_set(self::$tipTimeZone);

        self::$user    = $config['user'] ?? self::$user;
        self::$pidName = $config['pid']  ?? self::$pidName;
        self::$appName = $config['appName'] ?? self::$appName;
        $this -> _environmentCheck();
        $this -> _checkPidfile();
        $this -> _daemonMake();
        Log::Init();
        chdir(APP_PATH.DIRECTORY_SEPARATOR.APP_RUNTIME_DIR);
        $this -> _setUser(self::$user) or die("ArrowWorker hint : Setting process user failed！");
        $this -> _setProcessName("ArrowWorker V1.6 --By Louis --started at ".date("Y-m-d H:i:s"));
        $this -> _createPidfile();
    }

    public static function Start()
    {
        $config = static::_getConfig();
        //设置运行日志级别
        error_reporting( $config['errorLevel'] ?? static::$errorLevel );
        new self($config);
    }

    private static function _getConfig() : array
    {

        $config = Config::App('Daemon');
        if( false===$config  )
        {
            throw new \Exception(500,'Daemon http configuration not found');
        }

    }

    /**
     * _environmentCheck 运行环境/扩展检测
     * @author Louis
     */
    private function _environmentCheck()
    {
        if ( php_sapi_name() != "cli" )
        {
            die("ArrowWorker hint : only run in command line mode\n");
        }

        if ( !function_exists('pcntl_signal_dispatch') )
        {
            declare(ticks = 10);
        }

        if ( !function_exists('pcntl_signal') )
        {
            $message = 'ArrowWorker hint : php environment do not support pcntl_signal';
            die($message);
        }

        $fl = fopen(self::$output, 'a') or die("ArrowWorker hint : cannot create log file");
        fclose($fl);

        if ( function_exists('gc_enable') )
        {
            gc_enable();
        }

    }

    /**
     * _daemonMake  进程脱离终端
     * @author Louis
     */
    private function _daemonMake()
    {
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
    }

    /**
     * _createPidfile 创建进程pid文件
     * @author Louis
     */
    private function _createPidfile()
    {

        if (!is_dir(self::$pidDir))
        {
            mkdir(self::$pidDir);
        }

        $fp = fopen(self::$pid_File, 'w') or die("cannot create pid file");
        fwrite($fp, posix_getpid());
        fclose($fp);

        $this -> _writeLog("create pid file " . self::$pid_File);
    }

    /**
     * _checkPidfile 检测进程pid文件
     * @author Louis
     */
    private function _checkPidfile()
    {
        $pidFile = self::$pidDir . DIRECTORY_SEPARATOR . self::$pidName . ".pid";
        if (!file_exists($pidFile))
        {
            return true;
        }

        $pid = (int)file_get_contents($pidFile);

        if ($pid > 0 && posix_kill($pid, 0))
        {
            die("ArrowWorker hint : process is already started");
        }
        else
        {
            die("ArrowWorker hint : process ended abnormally , Check your program." . self::$pid_File);
        }

        die('checking pid file error');
    }


    /**
     * _setSignalHandler 进程信号处理设置
     * @author Louis
     */
    private function _setSignalHandler()
    {
        pcntl_signal(SIGCHLD, array(__CLASS__, "signalHandler"),false);
        pcntl_signal(SIGTERM, array(__CLASS__, "signalHandler"),false);
        pcntl_signal(SIGINT,  array(__CLASS__, "signalHandler"),false);
        pcntl_signal(SIGQUIT, array(__CLASS__, "signalHandler"),false);
    }


    /**
     * signalHandler 进程信号处理
     * @author Louis
     * @param int $signal
     * @return bool
     */
    public function signalHandler(int $signal)
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
                break;
            default:
                return false;
        }
        return false;
    }

    /**
     * _userSet 运行用户设置
     * @author Louis
     * @param string $name
     * @return bool
     */
    private function _setUser(string $name) : bool
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


    /**
     * _setProcessName  进程名称设置
     * @author Louis
     * @param string $proName
     */
    private function _setProcessName(string $proName)
    {
        $proName = self::$AppName.' : '.$proName;
        if(function_exists('cli_set_process_title'))
        {
            @cli_set_process_title($proName);
        }
        elseif(extension_loaded('proctitle')&&function_exists('setproctitle'))
        {
            @setproctitle($proName);
        }
    }

    /**
     * _writeLog 标准输出日志
     * @author Louis
     * @param string $message
     */
    private  function _writeLog(string $message)
    {
        @printf("%s\tpid:%d\tppid:%d\t%s\n", date("Y-m-d H:i:s"), posix_getpid(), posix_getppid(), $message);
    }

}