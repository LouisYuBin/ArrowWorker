<?php
/**
 * User: louis
 * Time: 18-6-7 下午2:38
 */

namespace ArrowWorker;


use ArrowWorker\Lib\System\LoadAverage;
use ArrowWorker\Lib\Process;

use ArrowWorker\Server\Tcp;
use ArrowWorker\Server\Http;
use ArrowWorker\Server\Ws;
use ArrowWorker\Server\Udp;

/**
 * Class Daemon : demonize process
 * @package ArrowWorker
 */
class Daemon
{
    const LOG_PREFIX = '[ Monitor ] ';

    const PROCESS_LOG = 'log';

    const PROCESS_TCP = 'Tcp';

    const PROCESS_UDP = 'Udp';

    const PROCESS_HTTP = 'Http';

    const PROCESS_WEBSOCKET = 'Ws';


    /**
     * appName : application name for service
     * @var mixed|string
     */
    const APP_NAME = 'Arrow';

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
        self::_createPidFile();

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

        $appList = APP_TYPE;
        rsort($appList);
        foreach ( $appList as $appType )
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
        $pid = Process::Fork();
        if($pid == 0)
        {
            Log::Dump(static::LOG_PREFIX.'starting log process ( '.Process::Id().' )');
            self::_setProcessName(static::PROCESS_LOG);
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
        $pid = Process::Fork();
        if($pid == 0)
        {
            Log::Dump(static::LOG_PREFIX.'starting worker process( '.Process::Id().' )');
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
            if( !isset($config['type']) || !isset($config['port']) || !in_array($config['type'], [ self::PROCESS_HTTP, self::PROCESS_WEBSOCKET, self::PROCESS_TCP, self::PROCESS_UDP]) )
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
        $pid = Process::Fork();
        if($pid == 0)
        {
            $pid = Process::Id();
            $processName = "{$config['type']} : {$config['port']}";
            Log::Dump(self::LOG_PREFIX."starting {$processName} process ( $pid )");
            self::_setProcessName($processName);
            if( $config['type']==self::PROCESS_HTTP )
            {
                Http::Start($config);
            }
            else if( $config['type']==self::PROCESS_WEBSOCKET )
            {
                Ws::Start($config);
            }
            else if( $config['type']==self::PROCESS_TCP )
            {
                Tcp::Start($config);
            }
            else if( $config['type']==self::PROCESS_UDP )
            {
                Udp::Start($config);
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
        Log::Dump(static::LOG_PREFIX.'starting monitor process ( '.Process::Id().' )');
        while (1)
        {
            if( self::$terminate )
            {
                //exit sequence: server -> worker -> log
                if( $this->_exitWorkerProcess('server') )
                {
                    if( $this->_exitWorkerProcess('worker') )
                    {
                        $this->_exitLogProcess();
                    }
                }

                $this->_exitMonitor();
            }

            pcntl_signal_dispatch();

            $status = 0;
            //returns the process ID of the child which exited, -1 on error or zero if WNOHANG was provided as an option (on wait3-available systems) and no child was available
            $pid = Process::Wait($status);
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
                Log::Dump(static::LOG_PREFIX."{$appType} process : {$pid} exited at status : {$status}");
                return ;
            }

            Log::Dump(static::LOG_PREFIX."{$appType} process restarting at status {$status}");

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
     * @param string $type
     * @return bool
     */
    private function _exitWorkerProcess(string $type='server')
    {
        $isExisted = true;
        foreach (static::$pidMap as $eachProcess)
        {
            if( $eachProcess['type']==$type )
            {
                $isExisted = false;
                $this->_exitProcess($eachProcess['type'], $eachProcess['pid']);
            }
        }
        return $isExisted;
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
        $signal = SIGTERM;
        if( !Process::IsKillNotified((string)($pid.$signal)) )
        {
            Log::Dump(static::LOG_PREFIX."sending SIGTERM signal to {$appType}:{$pid} process");
        }

        for($i=0; $i<3; $i++)
        {
            if( Process::Kill($pid,$signal) )
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

        Log::Dump(static::LOG_PREFIX.'exited');
        exit(0);
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
            Log::Hint("unknown operation, please use \"php {$argv[0]} start/stop/status/restart >> to start/stop/restart\" the service");
            exit(0);
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
                if( Process::Kill($pid,SIGTERM) )
                {
                    echo('Arrow stopping');
                }
                else
                {
                    Log::Hint('Arrow is not running.');
                    break ;
                }
            }
            else
            {
                if( !Process::Kill($pid,SIGTERM, true) )
                {
                    Log::Hint('stopped successfully.');
                    break;
                }
                else
                {
                    echo '.';
                    sleep(1);
                }
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
        exit(static::_processStatus());
    }

    /**
     * @return string
     */
    private static function _processStatus()
    {
        global $argv;
        $keyword = self::APP_NAME.'_'.self::$identity;
        if( PHP_OS=='Darwin')
        {
            $keyword = $argv[0] ;
        }
        $commend = "ps -e -o 'user,pid,ppid,args,pcpu,%mem' | grep {$keyword}";
        $output  = 'user | pid | ppid | process name | cpu usage | memory usage'.PHP_EOL;
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
        if (
            !is_array(APP_TYPE) ||
            0==count(APP_TYPE) ||
            (
                !in_array('server', APP_TYPE) &&
                !in_array('worker', APP_TYPE)
            )
        )
        {
            Log::DumpExit('APP_TYPE is not set.');
        }
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

        if ( Process::Fork() != 0)
        {
            exit();
        }

        posix_setsid();

        if ( Process::Fork() != 0)
        {
            exit();
        }

        self::$identity = self::APP_NAME.'_'.Process::Id();
    }

    /**
     * _createPidFile : create process pid file
     * @author Louis
     */
    private static function _createPidFile()
    {
        if (!is_dir(self::$pidDir))
        {
            mkdir(self::$pidDir);
        }

        $fp = fopen(self::$pid, 'w') or die("cannot create pid file".PHP_EOL);
        fwrite($fp, Process::Id());
        fclose($fp);
    }

    /**
     * _checkPidFile : checkout process pid file
     * @author Louis
     */
    private static function _checkPidFile()
    {
        if ( !file_exists(static::$pid) )
        {
            return ;
        }

        $pid = (int)file_get_contents(static::$pid);

        if ($pid > 0 && Process::Kill($pid, 0))
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
        Log::Dump(static::LOG_PREFIX."got a signal {$signal} : ".Process::SignalName($signal));
        switch($signal)
        {
            case SIGUSR1:
                self::$terminate = true;
                break;
            case SIGTERM:
            case SIGINT:
            case SIGQUIT:
                self::$terminate = true;
                var_dump(self::$pidMap);
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
        Process::SetName(self::$identity.'_'.$proName);
    }

}