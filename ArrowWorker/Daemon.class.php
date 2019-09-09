<?php
/**
 * User: louis
 * Time: 18-6-7 下午2:38
 */

namespace ArrowWorker;


use ArrowWorker\Driver\Channel;
use ArrowWorker\Lib\System\LoadAverage;

/**
 * Class Daemon : demonize process
 * @package ArrowWorker
 */
class Daemon
{
    const LOG_PREFIX = 'monitor : ';

    const PROCESS_LOG = 'log';

    const PROCESS_TCP = 'tcp';

    const PROCESS_UDP = 'udp';

    const PROCESS_HTTP = 'web';

    const PROCESS_WEBSOCKET = 'websocket';


    /**
     * appName : application name for service
     * @var mixed|string
     */
    const APP_NAME = 'Arrow';

    private static $components = [];

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
    private static $pid = 'Arrow';

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

    public static $identity = '';
    

    /**
     * ArrowDaemon constructor.
     */
    public function __construct()
    {
        //todo
    }

    /**
     * Start
     */
    public static function Start()
    {
        self::_initConfig();
        self::_handleAction();
        self::_checkEnvironment();
        self::_checkPidFile();
        self::_demonize();
        chdir(APP_PATH.DIRECTORY_SEPARATOR.APP_RUNTIME_DIR);
        self::_setProcessName("V1.6 --By Louis --started at ".date("Y-m-d H:i:s"));
        self::_createPidfile();

        $daemon = new self();
        $daemon->_initComponent();
        $daemon->_setSignalHandler();
        $daemon->_startProcess();
        $daemon->_startMonitor();
    }

    /**
     *
     */
    private function _initComponent()
    {
        Log::Init();
        Memory::Init();
    }

    /**
     * _startProcess
     */
    private function _startProcess()
    {
        $this->_startLogProcess();

        if ( !is_array(APP_TYPE) )
        {
            Log::Error('APP_TYPE is incorrect.');
        }

        foreach ( APP_TYPE as $appType )
        {
            if ( $appType=='server' )
            {
                $this->_startSwooleServer();
            }
            else if( $appType=='worker' )
            {
                $this->_startWorkerProcess();
            }
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
            static::_setProcessName(static::PROCESS_LOG);
            Log::Start();
        }
        else
        {
            static::$pidMap[] = [
                'pid'   => $pid,
                'type'  => static::PROCESS_LOG,
                'index' => 0
            ];
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
            self::_setProcessName('Worker-group monitor');
            Worker::Start();
        }
        else
        {
            static::$pidMap[] = [
                'pid'   => $pid,
                'type'  => 'worker',
                'index' => 0
            ];
        }
    }


    /**
     * @param int $pointedIndex
     */
    private function _startSwooleServer(int $pointedIndex=0)
    {
        $configs = Config::Get('Server');
        if( false===$configs || !is_array($configs) )
        {
            return ;
        }
        foreach ($configs as $index=>$config)
        {
            //必要配置不完整则不开启
            if( !isset($config['type']) || !isset($config['port']) || !in_array($config['type'],['web','webSocket','tcp','udp']) )
            {
                continue;
            }

            if( $pointedIndex==0 )  //start all swoole server
            {
                $this->_startPointedSwooleServer($config, $index);
            }
            else            // start specified swoole server only
            {
                if( $pointedIndex!=$index )
                {
                    continue ;
                }
                $this->_startPointedSwooleServer($config, $index);
            }
        }
    }

    /**
     * @param array $config
     * @param int   $index
     */
    private function _startPointedSwooleServer( array $config, int $index)
    {
        $pid = pcntl_fork();
        if($pid == 0)
        {
            $processName = "{$config['type']}-{$index} : {$config['port']}";
            Log::Dump(static::LOG_PREFIX."starting {$processName} process");
            static::_setProcessName($processName);
            if( $config['type']=='web' )
            {
                Swoole::StartHttpServer($config);
            }
            else if( $config['type']=='webSocket' )
            {
                Swoole::StartWebSocketServer($config);
            }
            else if( $config['type']=='tcp' )
            {
                Swoole::StartTcpServer($config);
            }
            else if( $config['type']=='udp' )
            {
                Swoole::StartUdpServer($config);
            }

            exit(0);
        }
        else
        {
            static::$pidMap[] = [
                'pid'   => $pid,
                'index' => $index,
                'type'  => 'server'
            ];
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
                $this->_exitWorkerProcess();
                $this->_exitLogProcess();
                $this->_exitMonitor();
            }

            pcntl_signal_dispatch();

            $status = 0;
            //returns the process ID of the child which exited, -1 on error or zero if WNOHANG was provided as an option (on wait3-available systems) and no child was available
            $pid = pcntl_wait($status, WUNTRACED);
            $this->_handleExitedProcess($pid, $status);
            pcntl_signal_dispatch();
            usleep(100000);
        }
    }

    /**
     * _handleExitedProcess
     * @param int $pid
     * @param int $status
     */
    private function _handleExitedProcess(int $pid, int $status)
    {
        foreach (static::$pidMap as $key=>$eachProcess)
        {
            if($eachProcess['pid'] != $pid)
            {
                continue;
            }

            $appType = $eachProcess['type'];
            $index   = $eachProcess['index'];

            unset(static::$pidMap[$key]);

            if( self::$terminate )
            {
                Log::Dump(static::LOG_PREFIX."{$appType}. process : {$pid} exited at status : {$status}.");
                return ;
            }

            Log::Dump(static::LOG_PREFIX.$appType.' process restarting at status : '.$status);

            if( $appType==static::PROCESS_LOG )
            {
                $this->_startLogProcess();
            }
            else if( $appType=='worker' )
            {
                $this->_startWorkerProcess();
            }
            else if( $appType=='server' )
            {
                $this->_startSwooleServer($index);
            }
        }
    }


    /**
     * _exitWorker
     */
    private function _exitWorkerProcess()
    {
        foreach (static::$pidMap as $eachProcess)
        {
            if( $eachProcess['type']!=static::PROCESS_LOG )
            {
                $this->_exitProcess($eachProcess['type'], $eachProcess['pid']);
            }
        }
    }

    /**
     * _exitLogProcess
     */
    private function _exitLogProcess()
    {
        //check whether there are only one process left
        if( count(static::$pidMap)>1 )
        {
            return ;
        }

        //check whether the process left is log process
        $logProcessId = 0;
        foreach (static::$pidMap as $eachProcess)
        {
            if( $eachProcess['type']==static::PROCESS_LOG )
            {
                $logProcessId = $eachProcess['pid'];
            }
        }

        //exit log process if only log process left
        if( $logProcessId>0 )
        {
            $this->_exitProcess(static::PROCESS_LOG, $logProcessId);
        }
    }

    /**
     * @param string $appType
     * @param int    $pid
     */
    private function _exitProcess( string $appType, int $pid)
    {
        Log::Dump(static::LOG_PREFIX." sending SIGTERM signal to {$appType}:{$pid} process");
        for($i=0; $i<3; $i++)
        {
            if( posix_kill($pid,SIGTERM) )
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
        Chan::Close();

        Log::DumpExit(static::LOG_PREFIX.'Monitor process exited!');
    }

    /**
     * handle current operation：exit daemon
     */
    private static function _handleAction()
    {
        global $argv;
        if( !isset($argv[1]) )
        {
            Log::DumpExit('use command << php index.php start/stop >> to start or stop Arrow Server');
        }

        $action = $argv[1];

        if( !in_array($action, ['start', 'stop', 'status', 'restart']) )
        {
            Log::DumpExit("unknown operation, use << php {$argv[0]} start/stop/status/restart >> to start/stop/restart Arrow");
        }

        switch ($action)
        {
            case 'stop':
                static::_restart(true);
                break;
            case 'start':
                static::_start();
                break;
            case 'status':
                static::_status();
                break;
            case 'restart':
                static::_restart(false);
                break;
            default:
        }

    }

    /**
     *
     */
    private static function _start()
    {
        Log::Hint('Arrow starting.');
    }

    /**
     * @param bool $isStop
     */
    private static function _restart( bool $isStop=true)
    {
        $pid = static::_getDaemonPid();
        for($i=1; $i>0; $i++ )
        {
            if( $i==1 )
            {
                if( posix_kill($pid,SIGTERM) )
                {
                    Log::Hint('Arrow exiting.');
                }
                else
                {
                    Log::Hint('Arrow is not running.');
                    break ;
                }
            }
            else
            {
                if( !posix_kill($pid,SIGTERM) )
                {
                    Log::Hint('Arrow stopped.');
                    break;
                }
                sleep(1);
            }
        }

        if( $isStop )
        {
            exit(0);
        }

        static::_start();
    }

    /**
     *
     */
    private static function _status()
    {
        Log::DumpExit(static::_processStatus());
    }

    /**
     * @return string
     */
    private static function _processStatus()
    {
        $keyword = self::APP_NAME.'_'.self::$identity;
        if( PHP_OS=='Darwin')
        {
            $keyword = 'index.php' ;
        }
        $commend = "ps -e -o 'user,pid,ppid,args,pcpu,%mem' | grep {$keyword}";
        $output  = str_pad('user',10).
            str_pad('pid',10).
            str_pad('ppid',10).
            str_pad('process name',25).
            str_pad('cpu usage',15).
            str_pad('memory usage',15).PHP_EOL;
        $results = LoadAverage::Exec($commend);
        foreach ($results as $key=>$item)
        {
            $output .= $item.PHP_EOL;
        }
        return $output;
    }

    /**
     * @return int
     */
    private static function _getDaemonPid() : int
    {
        $pid = (int)file_get_contents(static::$pid);
        if( $pid==0 )
        {
            Log::DumpExit('Arrow Server is not running');
        }
        return $pid;
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
     * _initConfig
     */
    private static function _initConfig()
    {
        self::$pid = static::$pidDir.static::APP_NAME.'.pid';
    }

    /**
     * _environmentCheck : checkout process running environment
     * @author Louis
     */
    private static function _checkEnvironment()
    {
        if ( php_sapi_name() != "cli" )
        {
            Log::DumpExit("Arrow hint : only run in command line mode");
        }

        if ( !function_exists('pcntl_signal_dispatch') )
        {
            declare(ticks = 10);
        }

        if ( !function_exists('pcntl_signal') )
        {
            Log::DumpExit('Arrow hint : php environment do not support pcntl_signal');
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
    private static function _demonize()
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

        self::$identity = self::APP_NAME.'_'.posix_getpid();
    }

    /**
     * _createPidfile : create process pid file
     * @author Louis
     */
    private static function _createPidfile()
    {
        if (!is_dir(self::$pidDir))
        {
            mkdir(self::$pidDir);
        }

        $fp = fopen(self::$pid, 'w') or die("cannot create pid file".PHP_EOL);
        fwrite($fp, posix_getpid());
        fclose($fp);
    }

    /**
     * _checkPidfile : checkout process pid file
     * @author Louis
     */
    private static function _checkPidFile()
    {
        if ( !file_exists(static::$pid) )
        {
            return ;
        }

        $pid = (int)file_get_contents(static::$pid);

        if ($pid > 0 && posix_kill($pid, 0))
        {
            Log::DumpExit("Arrow hint : process is already started");
        }
        else
        {
            unlink(self::$pid);
            //Log::DumpExit('Arrow hint : process ended abnormally , please delete pid file '. self::$pid.' and try again.');
        }

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
     * _setProcessName  set process name
     * @author Louis
     * @param string $proName
     */
    private static function _setProcessName(string $proName)
    {
        if( PHP_OS=='Darwin')
        {
            return ;
        }

        $proName = self::$identity.'_'.$proName;
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