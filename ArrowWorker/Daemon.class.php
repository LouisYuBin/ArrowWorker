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
    /**
     *
     */
    const LOG_PREFIX = '[ Monitor ] ';

    /**
     *
     */
    const PROCESS_LOG = 'log';

    /**
     *
     */
    const PROCESS_TCP = 'Tcp';

    /**
     *
     */
    const PROCESS_UDP = 'Udp';

    /**
     *
     */
    const PROCESS_HTTP = 'Http';

    /**
     *
     */
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
     * @var string
     */
    public static $identity = '';

    /**
     * pidMap : child process name
     * @var array
     */
    private $_pidMap = [];

    /**
     * terminate : is terminate process
     * @var bool
     */
    private $_terminate = false;

    public function __construct()
    {
        $this->_changeWorkDirectory();
        $this->_createPidFile();
        $this->_setProcessName("started at ".date("Y-m-d H:i:s"));
    }

    public static function Start()
    {
        self::_initConfig();
        self::_execCommand();
        self::_checkEnvironment();
        self::_checkPidFile();
        self::_demonize();

        $daemon = new self();
        $daemon->_initComponent();
        $daemon->_setSignalHandler();
        $daemon->_startProcess();
        $daemon->_startMonitor();
    }

    private function _initComponent()
    {
        Log::Initialize();
        Memory::Init();
    }

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

    private function _startLogProcess()
    {
        $pid = Process::Fork();
        if($pid == 0)
        {
            Log::Dump(static::LOG_PREFIX.'starting log process ( '.Process::Id().' )');
            $this->_setProcessName(static::PROCESS_LOG);
            Log::Start();
        }
        else
        {
            $this->_pidMap[] = [
                'pid'   => $pid,
                'type'  => self::PROCESS_LOG,
                'index' => 0
            ];
        }
    }

    private function _startWorkerProcess()
    {
        $pid = Process::Fork();
        if($pid == 0)
        {
            Log::Dump(static::LOG_PREFIX.'starting worker process( '.Process::Id().' )');
            $this->_setProcessName('Worker-group monitor');
            Worker::Start();
        }
        else
        {
            $this->_pidMap[] = [
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
            $this->_setProcessName($processName);
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
            $this->_pidMap[] = [
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
            if( $this->_terminate )
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
        foreach ($this->_pidMap as $key=>$eachProcess)
        {
            if($eachProcess['pid'] != $pid)
            {
                continue;
            }

            $appType = $eachProcess['type'];
            $index   = $eachProcess['index'];

            unset($this->_pidMap[$key]);

            if( $this->_terminate )
            {
                Log::Dump(self::LOG_PREFIX."{$appType} process : {$pid} exited at status : {$status}");
                return ;
            }

            Log::Dump(self::LOG_PREFIX."{$appType} process restarting at status {$status}");

            if( $appType==self::PROCESS_LOG )
            {
                $this->_startLogProcess();
            }
            else if( $appType=='worker' )
            {
                $this->_startWorkerProcess();
            }
            else if( $appType=='server' )
            {
                usleep(100000);
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
        foreach ($this->_pidMap as $eachProcess)
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
        if( count($this->_pidMap)>1 )
        {
            return ;
        }

        //check whether the process left is log process
        $logProcessId = 0;
        foreach ($this->_pidMap as $eachProcess)
        {
            if( $eachProcess['type']==self::PROCESS_LOG )
            {
                $logProcessId = $eachProcess['pid'];
            }
        }

        //exit log process if only log process left
        if( $logProcessId>0 )
        {
            $this->_exitProcess(self::PROCESS_LOG, $logProcessId);
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
        if( count($this->_pidMap)!=0 )
        {
            return ;
        }

        if( file_exists(self::$pid) )
        {
            unlink(self::$pid);
        }

        $this->_cleanChannelPath();
        Chan::Close();

        Log::Dump(static::LOG_PREFIX.'exited');
        exit(0);
    }


    private static function _execCommand()
    {
        global $argv;
        if( !isset($argv[1]) )
        {
            Log::DumpExit('use command << php index.php start/stop >> to start or stop Arrow Server');
        }

        $action = $argv[1];

        if( !in_array($action, ['start', 'stop', 'status', 'restart']) )
        {
            Log::Hint("Oops! Unknown operation. please use \"php {$argv[0]} start/stop/status/restart\" to start/stop/restart the service");
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
        $keyword = self::APP_NAME.'_'.self::_getDaemonPid();
        if( PHP_OS=='Darwin')
        {
            $keyword = $argv[0] ;
        }
        $commend = "ps -e -o 'user,pid,ppid,pcpu,%mem,args' | grep {$keyword}";
        $output  = 'user | pid | ppid | cpu usage | memory usage | process name'.PHP_EOL;
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
            Log::Hint('Arrow Hint : Server is not running');
            exit(0);
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
    private function _createPidFile()
    {
        if (!is_dir(self::$pidDir))
        {
            mkdir(self::$pidDir,0777,true);
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
            Log::Hint("Arrow Hint : Server is already started.");
            exit;
        }
        else
        {
            unlink(self::$pid);
        }

    }

    private function _changeWorkDirectory()
    {
        chdir(APP_PATH.DIRECTORY_SEPARATOR.APP_RUNTIME_DIR);
    }


    /**
     * _setSignalHandler : set handle function for process signal
     * @author Louis
     */
    private function _setSignalHandler()
    {
        pcntl_signal(SIGCHLD, [$this, "SignalHandler"],false);
        pcntl_signal(SIGTERM, [$this, "SignalHandler"],false);
        pcntl_signal(SIGINT,  [$this, "SignalHandler"],false);
        pcntl_signal(SIGQUIT, [$this, "SignalHandler"],false);
        // SIGTSTP have to be ignored on mac os
        pcntl_signal(SIGTSTP, SIG_IGN,false);

    }


    /**
     * SignalHandler : handle process signal
     * @author Louis
     * @param int $signal
     * @return bool
     */
    public function SignalHandler(int $signal)
    {
        //Log::Dump(static::LOG_PREFIX."got a signal {$signal} : ".Process::SignalName($signal));
        switch($signal)
        {
            case SIGUSR1:
                $this->_terminate = true;
                break;
            case SIGTERM:
            case SIGINT:
            case SIGQUIT:
                $this->_terminate = true;
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
    private function _setProcessName(string $proName)
    {
        Process::SetName(self::$identity.'_'.$proName);
    }

}