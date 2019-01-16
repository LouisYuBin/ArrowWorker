<?php
/**
 * User: louis
 * Time: 18-6-7 下午2:38
 */

namespace ArrowWorker;


use ArrowWorker\Driver\Channel\Queue;

/**
 * Class Daemon : demonize process
 * @package ArrowWorker
 */
class Daemon
{
    const LOG_PREFIX = 'monitor : ';

    /**
     * running user
     * @var string
     */
    private static $user = 'root';

    /**
     * 需要去除的进程执行权限
     * @var int
     */
    private static $umask = 0;

    /**
     * path of where pid file will be located
     * @var string
     */
    private static $pidDir = APP_PATH.DIRECTORY_SEPARATOR.APP_RUNTIME_DIR.'/Pid/';

    /**
     * $pid : pid file for monitor process
     * @var mixed|string
     */
    private static $pid = 'ArrowWorker';
    
    /**
     * appName : application name for service
     * @var mixed|string
     */
    private static $appName = 'ArrowWorker';

    /**
     * pidMap : child process name
     * @var array
     */
    private static $pidMap = [];

    /**
     * terminate : is terminate process
     * @var bool
     */
    private static $terminate = false;
    

    /**
     * ArrowDaemon constructor.
     * @param array $config
     */
    public function __construct($config)
    {

        $this -> _environmentCheck();
        $this -> _checkPidfile();
        Log::Init();

        $this -> _demonize();
        chdir(APP_PATH.DIRECTORY_SEPARATOR.APP_RUNTIME_DIR);
        $this -> _setUser(self::$user) or die("ArrowWorker hint : Setting process user failed！".PHP_EOL);
        $this -> _setProcessName(" V1.6 --By Louis --started at ".date("Y-m-d H:i:s"));
        $this -> _createPidfile();
    }

    /**
     * Start
     */
    public static function Start()
    {
        $config = static::_getConfig();
        static::_handleAction();

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

        if ( is_array(APP_TYPE) )
        {
            foreach ( APP_TYPE as $appType )
            {
                if ( $appType=='swHttp' )
                {
                    $this->_startSwHttpProcess();
                }
                else if( $appType=='worker' )
                {
                    $this->_startWorkerProcess();
                }
            }
        }
        else if( APP_TYPE=='swHttp' )
        {
            $this->_startSwHttpProcess();
        }
        else if( APP_TYPE=='worker' )
        {
            $this->_startWorkerProcess();
        }
    }

    /**
     * _startLogProcess
     */
    private function _startLogProcess()
    {
        $pid = pcntl_fork();
        if($pid == 0)
        {
           Log::Dump(static::LOG_PREFIX.'starting log process');
            static::_setProcessName('log');
            Log::Start();
        }
        else
        {
            static::$pidMap['log'] = $pid;
        }
    }

    /**
     * _startWorkerProcess
     */
    private function _startWorkerProcess()
    {
        $pid = pcntl_fork();
        if($pid == 0)
        {
            Log::Dump(static::LOG_PREFIX.'starting worker process');
            static::_setProcessName('Worker monitor');
            Worker::Start();
        }
        else
        {
            static::$pidMap['worker'] = $pid;
        }
    }

    /**
     * _startSwHttpProcess
     */
    private function _startSwHttpProcess()
    {
        $pid = pcntl_fork();
        if($pid == 0)
        {
            Log::Dump(static::LOG_PREFIX.'starting swoole http process');
            static::_setProcessName(static::$appName.'swoole http');
            Swoole::Http();
            exit(0);
        }
        else
        {
            static::$pidMap['swHttp'] = $pid;
        }
    }

    /**
     * _startMonitor : start monitor process
     * @author Louis
     */
    private function _startMonitor()
    {
        Log::Dump(static::LOG_PREFIX.'starting monitor');
        while (1)
        {
            if( self::$terminate )
            {
                $this->_exitSwooleApp('swHttp');
                $this->_exitSwooleApp('swWebsocket');
                $this->_exitWorker();
                $this->_exitLog();
                $this->_exitMonitor();
            }

            pcntl_signal_dispatch();

            $status = 0;
            //returns the process ID of the child which exited, -1 on error or zero if WNOHANG was provided as an option (on wait3-available systems) and no child was available
            $pid = pcntl_wait($status, WUNTRACED);
            $this->_handleExitedProcess($pid, $status);
            pcntl_signal_dispatch();
            usleep(1000);
        }
    }

    /**
     * _handleExitedProcess
     * @param int $pid
     * @param int $status
     */
    private function _handleExitedProcess(int $pid, int $status)
    {
        foreach (static::$pidMap as $appType=>$prePid)
        {
            if($pid != $prePid)
            {
                continue;
            }

            unset(static::$pidMap[$appType]);

            if( self::$terminate )
            {
                Log::Dump(static::LOG_PREFIX.$appType.' process exited at status : '.$status);
                return ;
            }

            Log::Dump(static::LOG_PREFIX.$appType.' process restarting at status : '.$status);

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


    /**
     * _exitSwHttp
     */
    private function _exitSwooleApp(string $appType)
    {
        if ( !isset(static::$pidMap[$appType]) )
        {
            return ;
        }

        Log::Dump(static::LOG_PREFIX.'sending SIGTERM signal to '.$appType.' process');
        for($i=0; $i<3; $i++)
        {
            if( posix_kill(static::$pidMap[$appType],SIGTERM) )
            {
                break ;
            }
            usleep(1000);
        }
    }

    /**
     * _exitWorker
     */
    private function _exitWorker()
    {
        $appType = 'worker';
        if ( !isset(static::$pidMap['worker']) ||
              isset(static::$pidMap['swHttp']) ||
              isset(static::$pidMap['swWebsocket']) )
        {
            return ;
        }

        Log::Dump(static::LOG_PREFIX.'sending SIGTERM signal to '.$appType.' process');
        for($i=0; $i<3; $i++)
        {
            if( posix_kill(static::$pidMap[$appType],SIGTERM) )
            {
                break ;
            }
            usleep(1000);
        }
    }

    /**
     * _exitLog
     */
    private function _exitLog()
    {
        if( count(static::$pidMap)!=1 || !isset(static::$pidMap['log']) )
        {
           return ;
        }

        Log::Dump(static::LOG_PREFIX.'send signal to log process');

        for($i=0; $i<3; $i++)
        {
            if( posix_kill(static::$pidMap['log'],SIGTERM) )
            {
                break ;
            }
            usleep(1000);
        }
    }

    /**
     * _exitMonitor
     */
    private function _exitMonitor()
    {
        if( count(static::$pidMap)!=0 )
        {
            return ;
        }

        if( file_exists(static::$pid) )
        {
            unlink(static::$pid);
        }

        $this->_cleanChannelPath();
        Queue::Close();

        Log::Dump(static::LOG_PREFIX.'Monitor process exited!');
        exit(0);
    }

    /**
     * handle current operation：exit daemon
     */
    private function _handleAction()
    {
        global $argv;
        if( !isset($argv[1]) )
        {
            return ;
        }

        if( $argv[1] !='stop' )
        {
            return ;
        }

        $pid = (int)file_get_contents(static::$pid);
        if( $pid==0 )
        {
            return ;
        }

        for($i=1; $i>0; $i++ )
        {
            if( $i==1 )
            {
                if( posix_kill($pid,SIGTERM) )
                {
                    Log::Dump('ArrowWorker process is exiting...');
                }
                else
                {
                    Log::Dump('ArrowWorker process does not exists.');
                    exit;
                }
            }
            else
            {
                if( !posix_kill($pid,SIGTERM) )
                {
                    Log::Dump('ArrowWorker process is been stopped.');
                    exit ;
                }
                usleep(100000);
            }
        }

    }

    /**
     * _cleanChannelPath
     */
    private function _cleanChannelPath()
    {
        $chanPath  = APP_PATH.DIRECTORY_SEPARATOR.APP_RUNTIME_DIR.'/Channel/';
        $chanFiles = scandir($chanPath);
        if( $chanFiles!==false )
        {
            foreach ($chanFiles as $file)
            {
                if( $file=='.' || $file=='..' || is_dir($chanPath.$file))
                {
                    continue ;
                }
                @unlink($chanPath.$file);
            }
        }
    }

    /**
     * _getConfig
     * @return array
     */
    private static function _getConfig() : array
    {

        $config = Config::Get('Daemon');
        if( false===$config  )
        {
            Log::Dump(static::LOG_PREFIX.'Daemon configuration not found');
            return [];
        }
        self::$user = $config['user'] ?? self::$user;
        self::$pid  = static::$pidDir.static::$appName.'.pid';
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
            die("ArrowWorker hint : only run in command line mode".PHP_EOL);
        }

        if ( !function_exists('pcntl_signal_dispatch') )
        {
            declare(ticks = 10);
        }

        if ( !function_exists('pcntl_signal') )
        {
            die('ArrowWorker hint : php environment do not support pcntl_signal'.PHP_EOL);
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
    private function _demonize()
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

        $fp = fopen(self::$pid, 'w') or die("cannot create pid file".PHP_EOL);
        fwrite($fp, posix_getpid());
        fclose($fp);

       Log::Dump(static::LOG_PREFIX."creating pid file " . self::$pid);
    }

    /**
     * _checkPidfile : checkout process pid file
     * @author Louis
     */
    private function _checkPidfile()
    {
        if (!file_exists(static::$pid))
        {
            return true;
        }

        $pid = (int)file_get_contents(static::$pid);

        if ($pid > 0 && posix_kill($pid, 0))
        {
            die("ArrowWorker hint : process is already started".PHP_EOL);
        }
        else
        {
            die("ArrowWorker hint : process ended abnormally , Check your program." . self::$pid.PHP_EOL);
        }

        die('checking pid file error'.PHP_EOL);
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
        // SIGTSTP have to be ignored on mac os
        pcntl_signal(SIGTSTP, SIG_IGN,false);

    }


    /**
     * signalHandler : handle process signal
     * @author Louis
     * @param int $signal
     * @return bool
     */
    public function signalHandler(int $signal)
    {
       Log::Dump(static::LOG_PREFIX.'monitor process got a signal : '.$signal);
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
        if( PHP_OS=='Darwin')
        {
            return ;
        }

        $proName = self::$appName.'_'.$proName;
        if(function_exists('cli_set_process_title'))
        {
            @cli_set_process_title($proName);
        }
        if(extension_loaded('proctitle') && function_exists('setproctitle'))
        {
            @setproctitle($proName);
        }
    }

}