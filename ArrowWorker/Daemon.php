<?php

namespace ArrowWorker;

use ArrowWorker\Component\Worker\Arrow as Worker;
use ArrowWorker\Component\Worker\ArrowCoroutines;
use ArrowWorker\Component\Worker\ArrowCoroutines as WorkerCoroutines;
use ArrowWorker\HttpServer\Server as HttpServer;
use ArrowWorker\Library\Process;
use ArrowWorker\Log\Log;
use ArrowWorker\Server\Tcp;
use ArrowWorker\Server\Udp;
use ArrowWorker\Server\Ws;

/**
 * Class Daemon : demonize process
 * @package ArrowWorker
 */
class Daemon
{


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
     *
     */
    const APP_SERVER = 'server';

    /**
     * 进程
     */
    const APP_WORKER = 'worker';

    /**
     * 进程+写成
     */
    const APP_COROUTINES = 'coroutines';

    /**
     * path of where pid file will be located
     * @var string
     */
    const PID_DIR = APP_PATH . DIRECTORY_SEPARATOR . APP_RUNTIME_DIR . '/Pid/';

    /**
     * 需要去除的进程执行权限
     * @var int
     */
    private static $umask = 0;

    /**
     * $pid : pid file for monitor process
     * @var mixed|string
     */
    private const PID = self::PID_DIR . self::APP_NAME . '.pid';

    /**
     * @var string
     */
    public static string $identity = '';

    /**
     * @var bool
     */
    private static bool $isDebug = false;

    /**
     * @var array
     */
    private array $applications = [];

    /**
     * @var array
     */
    private array $serverClassAlias = [
        self::PROCESS_HTTP      => HttpServer::class,
        self::PROCESS_WEBSOCKET => Ws::class,
        self::PROCESS_TCP       => Tcp::class,
        self::PROCESS_UDP       => Udp::class
    ];

    /**
     * @var $container Container
     */
    private Container $container;

    /**
     * @var $logger Log
     */
    private Log $logger;


    /**
     * pidMap : child process name
     * @var array
     */
    private array $pidMap = [];

    /**
     * terminate : is terminate process
     * @var bool
     */
    private bool $terminate = false;


    /**
     * Daemon constructor.
     * @param Container $container
     * @param string $application
     * @param bool $isDebug
     */
    public function __construct(Container $container, string $application, bool $isDebug = false)
    {
        set_time_limit(0);
        $this->container = $container;
        $this->initParameter($application, $isDebug);
        $this->initFunction();
    }

    /**
     *
     */
    public function start(): void
    {
        $this->changeWorkDirectory();
        $this->demonize();
        $this->createPidFile();
        $this->setProcessName("started at " . date("Y-m-d H:i:s"));
        $this->initComponent();
        $this->setSignalHandler();
        $this->startProcess();
        $this->startMonitor();
    }

    /**
     * @param bool $isDebug
     */
    public function setDemonize(bool $isDebug): void
    {
        self::$isDebug = $isDebug;
    }

    /**
     * @param string $apps
     */
    public function setStartApp(string $apps): void
    {
        $appList = explode('|', $apps);
        foreach ($appList as $app) {
            $app = strtolower($app);
            if (!in_array($app, [
                self::APP_SERVER,
                self::APP_WORKER,
                self::APP_COROUTINES
            ])) {
                continue;
            }
            $this->applications[] = $app;
        }

        if (empty($this->applications)) {
            $this->applications = [self::APP_SERVER];
        }
    }

    /**
     *
     */
    private function initComponent(): void
    {
        $this->container->get(Chan::class, [$this->container]);
        $this->logger = $this->container->get(Log::class, [$this->container]);
        $this->container->get(Memory::class, [$this->container]);
    }

    /**
     *
     */
    private function startProcess(): void
    {
        $this->startLogProcess();

        rsort($this->applications);
        foreach ($this->applications as $appType) {
            if ($appType === self::APP_SERVER) {
                $this->startSwooleServer();
            } else if ($appType === self::APP_COROUTINES) {
                $this->startWorkerCoroutineProcess();
            } else if ($appType === self::APP_WORKER) {
                $this->startWorkerProcess();
            }
        }

    }

    /**
     *
     */
    private function startLogProcess(): void
    {
        $processNUm = $this->logger->GetProcessNum();
        for ($i = 0; $i < $processNUm; $i++) {
            $pid = Process::fork();
            if ($pid === 0) {
                Log::Dump('starting log process ( ' . Process::id() . ' )', Log::TYPE_DEBUG, __METHOD__);
                $this->setProcessName(static::PROCESS_LOG);
                $this->logger->getProcess()->start();
            } else {
                $this->pidMap[] = [
                    'pid'   => $pid,
                    'type'  => self::PROCESS_LOG,
                    'index' => 0,
                ];
            }
        }

    }

    /**
     *
     */
    private function startWorkerCoroutineProcess(): void
    {
        $pid = Process::fork();
        if ($pid === 0) {
            Log::Dump('starting worker process( ' . Process::id() . ' )', Log::TYPE_DEBUG, __METHOD__);
            $this->setProcessName('Worker-group master');
            $this->container->get(WorkerCoroutines::class, [$this->container, $this->logger])->start();
        } else {
            $this->pidMap[] = [
                'pid'   => $pid,
                'type'  => self::APP_COROUTINES,
                'index' => 0,
            ];
        }
    }

    /**
     *
     */
    private function startWorkerProcess(): void
    {
        $pid = Process::fork();
        if ($pid === 0) {
            Log::Dump('starting worker process( ' . Process::id() . ' )', Log::TYPE_DEBUG, __METHOD__);
            $this->setProcessName('Worker-group master');
            $this->container->get(Worker::class, [$this->container, $this->logger])->start();
        } else {
            $this->pidMap[] = [
                'pid'   => $pid,
                'type'  => 'worker',
                'index' => 0,
            ];
        }
    }


    /**
     * @param int $pointedIndex
     */
    private function startSwooleServer(int $pointedIndex = 0): void
    {
        $configs = Config::get('Server');
        if (false === $configs || !is_array($configs)) {
            return;
        }
        foreach ($configs as $index => $config) {
            //必要配置不完整则不开启
            if (!isset($config['type'], $config['port'], $this->serverClassAlias[$config['type']])) {
                continue;
            }

            if ($pointedIndex === 0)  //start all swoole server
            {
                $this->startPointedSwooleServer($config, $index);
            } else            // start specified swoole server only
            {
                if ($pointedIndex !== $index) {
                    continue;
                }
                $this->startPointedSwooleServer($config, $index);
            }
        }
    }

    /**
     * @param array $config
     * @param int $index
     */
    private function startPointedSwooleServer(array $config, int $index): void
    {
        $pid = Process::fork();
        if ($pid === 0) {
            $pid                = Process::id();
            $config['identity'] = self::$identity;

            $processName = "{$config['type']}:{$config['port']} Master";
            Log::Dump("starting {$processName} process ( {$pid} )", Log::TYPE_DEBUG, __METHOD__);
            $this->setProcessName($processName);
            if (isset($this->serverClassAlias[$config['type']])) {
                $this->container->make($this->serverClassAlias[$config['type']], [$this->container, $this->logger, $config])->start();
            }
            exit(0);
        }

        $this->pidMap[] = [
            'pid'   => $pid,
            'index' => $index,
            'type'  => self::APP_SERVER,
        ];
    }

    /**
     *
     */
    private function startMonitor(): void
    {
        Log::Dump('starting monitor process ( ' . Process::id() . ' )', Log::TYPE_DEBUG, __METHOD__);
        while (1) {
            if ($this->terminate) {
                //exit sequence: server -> worker -> log
                if ($this->exitWorkerProcess('server')) {
                    if ($this->exitWorkerProcess('worker')) {
                        $this->exitWorkerProcess('log');
                    }
                }

                $this->exitMonitor();
            }

            pcntl_signal_dispatch();

            $status = 0;
            //returns the process ID of the child which exited, -1 on error or zero if WNOHANG was provided as an option (on wait3-available systems) and no child was available
            $pid = Process::wait($status);
            $this->handleExitedProcess($pid, $status);
            pcntl_signal_dispatch();
            usleep(100000);
        }
    }

    /**
     * handleExitedProcess
     * @param int $pid
     * @param int $status
     */
    private function handleExitedProcess(int $pid, int $status): void
    {
        foreach ($this->pidMap as $key => $eachProcess) {
            if ($eachProcess['pid'] !== $pid) {
                continue;
            }

            $appType = $eachProcess['type'];
            $index   = $eachProcess['index'];

            unset($this->pidMap[$key]);

            if ($this->terminate) {
                Log::Dump("{$appType} process : {$pid} exited at status : {$status}", Log::TYPE_DEBUG, __METHOD__);
                return;
            }

            Log::Dump("{$appType} process restarting at status {$status}", Log::TYPE_DEBUG, __METHOD__);

            if ($appType === self::PROCESS_LOG) {
                $this->startLogProcess();
            } else if ($appType === self::APP_COROUTINES) {
                $this->startWorkerCoroutineProcess();
            } else if ($appType === self::APP_WORKER) {
                $this->startWorkerProcess();
            } else if ($appType === self::APP_SERVER) {
                usleep(100000);
                $this->startSwooleServer($index);
            }
        }
    }


    /**
     * @param string $type
     * @return bool
     */
    private function exitWorkerProcess(string $type = 'server'): bool
    {
        $isExisted = true;
        foreach ($this->pidMap as $eachProcess) {
            if ($eachProcess['type'] === $type) {
                $isExisted = false;
                $this->exitProcess($type, $eachProcess['pid']);
            }
        }
        return $isExisted;
    }

    /**
     * @param string $appType
     * @param int $pid
     */
    private function exitProcess(string $appType, int $pid): void
    {
        $signal = SIGTERM;
        if (!Process::isKillNotified((string)($pid . $signal))) {
            Log::Dump("sending SIGTERM signal to {$appType}:{$pid} process", Log::TYPE_DEBUG, __METHOD__);
        }

        for ($i = 0; $i < 3; $i++) {
            if (Process::kill($pid, $signal)) {
                break;
            }
            usleep(1000);
        }
    }

    /**
     *
     */
    private function exitMonitor(): void
    {
        if (!empty($this->pidMap)) {
            return;
        }

        if (file_exists(self::PID)) {
            unlink(self::PID);
        }

        Chan::close();

        Log::Dump('exited', Log::TYPE_DEBUG, __METHOD__);
        exit(0);
    }

    /**
     * @return int
     */
    public static function GetPid(): int
    {
        if (!file_exists(self::PID)) {
            return 0;
        }

        return (int)file_get_contents(self::PID);
    }

    /**
     * @param string $application
     * @param bool $isDebug
     */
    private function initParameter(string $application, bool $isDebug): void
    {
        $this->initPid();
        $this->setStartApp($application);
        $this->setDemonize($isDebug);
    }

    /**
     *
     */
    private function initFunction(): void
    {
        if (!function_exists('pcntl_signal_dispatch')) {
            declare(ticks=10);
        }

        if (!function_exists('pcntl_signal')) {
            Log::DumpExit('Arrow hint : php environment do not support pcntl_signal');
        }

        if (function_exists('gc_enable')) {
            gc_enable();
        }

    }

    /**
     *
     */
    private function demonize(): void
    {
        if (self::$isDebug) {
            return;
        }

        umask(self::$umask);

        if (Process::fork() !== 0) {
            exit();
        }

        posix_setsid();

        if (Process::fork() !== 0) {
            exit();
        }
    }

    /**
     *
     */
    private function createPidFile(): void
    {
        self::$identity = self::APP_NAME . '_' . Process::id();

        if (!is_dir(self::PID_DIR) && !mkdir(self::PID_DIR, 0766, true) && !is_dir(self::PID_DIR)) {
            Log::Dump("failed while creating pid directory", LOg::TYPE_ERROR, __METHOD__);
            exit;
        }

        $fp = fopen(self::PID, 'w') or die("cannot create pid file" . PHP_EOL);
        fwrite($fp, Process::id());
        fclose($fp);
    }

    /**
     *
     */
    private function initPid(): void
    {
        $pidFilePath = self::PID;
        if (!file_exists($pidFilePath)) {
            return;
        }

        $pid = (int)file_get_contents($pidFilePath);

        if ($pid > 0 && Process::kill($pid, 0)) {
            Log::Hint("Arrow Hint : Server is already started.");
            exit;
        }

        unlink($pidFilePath);
    }

    /**
     *
     */
    private function changeWorkDirectory(): bool
    {
        return chdir(APP_PATH . DIRECTORY_SEPARATOR . APP_RUNTIME_DIR);
    }


    /**
     * setSignalHandler : set handle function for process signal
     * @author Louis
     */
    private function setSignalHandler(): void
    {
        pcntl_signal(SIGCHLD, [
            $this,
            "SignalHandler",
        ], false);
        pcntl_signal(SIGTERM, [
            $this,
            "SignalHandler",
        ], false);
        pcntl_signal(SIGINT, [
            $this,
            "SignalHandler",
        ], false);
        pcntl_signal(SIGQUIT, [
            $this,
            "SignalHandler",
        ], false);
        // SIGTSTP have to be ignored on mac os
        pcntl_signal(SIGTSTP, SIG_IGN, false);

    }


    /**
     * SignalHandler : handle process signal
     * @param int $signal
     * @return bool
     * @author Louis
     */
    public function SignalHandler(int $signal): bool
    {
        //Log::Dump(static::MODULE_NAME."got a signal {$signal} : ".Process::SignalName($signal));
        switch ($signal) {
            case SIGUSR1:
            case SIGTERM:
            case SIGINT:
            case SIGQUIT:
                $this->terminate = true;
                break;
            default:
                return false;
        }
        return false;
    }


    /**
     * setProcessName  set process name
     * @param string $proName
     * @author Louis
     */
    private function setProcessName(string $proName): void
    {
        Process::setName(self::$identity . '_' . $proName);
    }

}