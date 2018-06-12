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
     * pid文件路径
     * @var string
     */
    private static $pidDir = APP_PATH.DIRECTORY_SEPARATOR.APP_RUNTIME_DIR.'/Pid/';

    /**
     * pidName
     * @var mixed|string
     */
    private static $pidName = 'ArrowWorker';

    private static $tipTimeZone='UTC';

    private static $appName = 'ArrowWorker demo';

    private static $pidMap = [];

    private static $terminate = false;
    

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
        Log::Init();

        $this -> _daemonMake();
        chdir(APP_PATH.DIRECTORY_SEPARATOR.APP_RUNTIME_DIR);
        $this -> _setUser(self::$user) or die("ArrowWorker hint : Setting process user failed！");
        $this -> _setProcessName("ArrowWorker V1.6 --By Louis --started at ".date("Y-m-d H:i:s"));
        $this -> _createPidfile();
    }

    public static function Start()
    {
        $config = static::_getConfig();
        $daemon = new self($config);
        $daemon->_setSignalHandler();
        $daemon->_startProcess();
        $daemon->_startMonitor();
    }

    /**
     * _startProcess
     */
    private function _startProcess()
    {

        $this->_startLogProcess();

        if(APP_TYPE=='swHttp')
        {
            $this->_startSwHttpProcess();
        }

        if(APP_TYPE=='worker')
        {
            $this->_startWorkerProcess();
        }
    }

    private function _startLogProcess()
    {
        $pid = pcntl_fork();
        if($pid == 0)
        {
            Log::Dump('starting log process');
            static::_setProcessName(static::$appName.'_log');
            Log::Start();
        }
        else
        {
            static::$pidMap[$pid] = 'log';
        }
    }

    private function _startWorkerProcess()
    {
        $pid = pcntl_fork();
        if($pid == 0)
        {
            Log::Dump('starting worker process');
            static::_setProcessName(static::$appName.'_Worker monitor');
            Worker::Start();
        }
        else
        {
            static::$pidMap[$pid] = APP_TYPE;
        }
    }

    private function _startSwHttpProcess()
    {
        $pid = pcntl_fork();
        if($pid == 0)
        {
            Log::Dump('starting swoole http process');
            static::_setProcessName(static::$appName.'_swoole http');
            Swoole::Http();
            Log::Dump('swoole http exited');
            exit();

        }
        else
        {
            static::$pidMap[$pid] = APP_TYPE;
        }
    }

    /**
     * _exitWorkers 开启worker监控
     * @author Louis
     */
    private function _startMonitor()
    {
        Log::Dump('starting monitor');
        while (1)
        {
            if( self::$terminate )
            {
                Log::Dump('starting Exit');
                $this->_exitProcess();
                $this->_exitLog();
                $this->_exitMonitor();
            }

            pcntl_signal_dispatch();

            $status = 0;
            //returns the process ID of the child which exited, -1 on error or zero if WNOHANG was provided as an option (on wait3-available systems) and no child was available
            $pid = pcntl_wait($status, WUNTRACED);
            $this->_handleExitedProcess($pid, $status);
            pcntl_signal_dispatch();
            sleep(1);
        }
    }

    private function _handleExitedProcess(int $pid, int $status)
    {
        foreach (static::$pidMap as $prePid=>$appType)
        {
            if($pid != $prePid)
            {
                continue;
            }

            if( self::$terminate )
            {
                unset(static::$pidMap[$pid]);
                Log::Dump($appType.' process exited : at status : '.$status);
                return ;
            }

            Log::Dump($appType.' process restarting : at status : '.$status);

            if( $appType=='log' )
            {
                $this->_startLogProcess();
            }
            else if( $appType=='worker' )
            {
                $this->_startWorkerProcess();
            }
            else if( $appType=='swHttp' )
            {
                $this->_startSwHttpProcess();
            }
        }
    }

    private function _exitProcess()
    {
        foreach (static::$pidMap as $pid=>$appType)
        {
            if($pid==0 || $appType=='log')
            {
                continue;
            }
            Log::Dump('sending SIGTERM signal to '.$appType.' process');
            for($i=0; $i<3; $i++)
            {
                if( posix_kill($pid,SIGTERM) )
                {
                    break ;
                }
                usleep(1000);
            }
        }
    }

    private function _exitLog()
    {
        Log::Dump('in _exitLog');
        if( count(static::$pidMap)!=1 )
        {
           return ;
        }

        Log::Dump('send signal to log process');
        foreach (static::$pidMap as $pid=>$appType)
        {
            if($appType != 'log')
            {
                continue ;
            }
            posix_kill($pid,SIGTERM);
        }

        $pid = pcntl_wait($status,WUNTRACED);
        unset(static::$pidMap[$pid]);
        Log::Dump('log process exited at status : '.$status);

    }

    private function _exitMonitor()
    {
        if( count(static::$pidMap)!=0 )
        {
            return ;
        }
        Log::Dump('Monitor process exited!');
        exit(0);
    }

    private static function _getConfig() : array
    {

        $config = Config::App('Daemon');
        if( false===$config  )
        {
            die('Daemon configuration not found');
        }
        return $config;
    }

    /**
     * _environmentCheck : checkout process running environment
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

        if ( function_exists('gc_enable') )
        {
            gc_enable();
        }

    }

    /**
     * _daemonMake : demonize the process
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
     * _createPidfile : create process pid file
     * @author Louis
     */
    private function _createPidfile()
    {

        if (!is_dir(self::$pidDir))
        {
            mkdir(self::$pidDir);
        }

        $fp = fopen(self::$pidName, 'w') or die("cannot create pid file");
        fwrite($fp, posix_getpid());
        fclose($fp);

        Log::Dump("creating pid file " . self::$pidName);
    }

    /**
     * _checkPidfile : checkout process pid file
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
            die("ArrowWorker hint : process ended abnormally , Check your program." . self::$pidName);
        }

        die('checking pid file error');
    }


    /**
     * _setSignalHandler : set handle function for process signal
     * @author Louis
     */
    private function _setSignalHandler()
    {
        pcntl_signal(SIGCHLD, [$this, "signalHandler"],false);
        pcntl_signal(SIGTERM, [$this, "signalHandler"],false);
        pcntl_signal(SIGINT,  [$this, "signalHandler"],false);
        pcntl_signal(SIGQUIT, [$this, "signalHandler"],false);
    }


    /**
     * signalHandler : handle process signal
     * @author Louis
     * @param int $signal
     * @return bool
     */
    public function signalHandler(int $signal)
    {
        switch($signal)
        {
            case SIGUSR1:
                self::$terminate = true;
                break;
            case SIGTERM:
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
     * _userSet set process running user
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
     * _setProcessName  set process name
     * @author Louis
     * @param string $proName
     */
    private function _setProcessName(string $proName)
    {
        $proName = self::$appName.' : '.$proName;
        if(function_exists('cli_set_process_title'))
        {
            @cli_set_process_title($proName);
        }
        elseif(extension_loaded('proctitle')&&function_exists('setproctitle'))
        {
            @setproctitle($proName);
        }
    }

}