<?php

namespace ArrowWorker;

use ArrowWorker\Library\Process;
use ArrowWorker\Library\System\LoadAverage;

class Console
{

    /**
     * @var Container $container
     */
    private $container;

    private $argv = [];

    private $entryFile = '';

    private $application = '';

    private $action = '';

    private $env = '';

    private $isDebug = false;


    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->checkStartEnv();
        $this->parseArgv();
        $this->_checkExtension();

        $this->container->Get(Config::class, [$this->GetEnv()]);
    }

    private function _stop()
    {
        $pid = Daemon::GetPid();
        if (0 === $pid) {
            Log::Hint('Arrow is not running.');
            return false;
        }

        for ($i = 1; $i > 0; $i++) {
            if ($i == 1) {
                if (Process::Kill($pid, SIGTERM)) {
                    echo('Arrow stopping');
                } else {
                    Log::Hint('Arrow is not running.');
                    return false;
                }
            } else {
                if (!Process::Kill($pid, SIGTERM, true)) {
                    Log::Hint('stopped successfully.');
                    return true;
                } else {
                    echo '.';
                    sleep(1);
                }
            }
        }
    }

    public function Execute(): void
    {
        switch ($this->action) {
            case 'stop':
                $this->_stop();
                break;
            case 'start':
                $this->_start();
                break;
            case 'status':
                $this->_getStatus();
                break;
            case 'restart':
                $this->_restart();
                break;
            case 'gen':
                break;
            default:
                Log::Hint("Oops! Unknown operation. please use \"php {$this->entryFile} start/stop/status/restart\" to start/stop/restart the service");
        }
        return;
    }

    private function _start()
    {
        Log::Hint("starting ...{$this->application}({$this->env})");
        $this->container->Get(Daemon::class, [
            $this->container,
            $this->application,
            $this->isDebug,
        ])->Start();
    }

    private function _getStatus()
    {
        $keyword = PHP_OS == 'Darwin' ? $this->entryFile : Daemon::APP_NAME . '_' . Daemon::GetPid();
        $commend = "ps -e -o 'user,pid,ppid,pcpu,%mem,args' | grep {$keyword}";
        $output = 'user | pid | ppid | cpu usage | memory usage | process name' . PHP_EOL;
        $results = LoadAverage::Exec($commend);
        $output .= implode(PHP_EOL, $results);
        echo $output . PHP_EOL;
    }

    private function _restart()
    {
        if ($this->_stop()) {
            $this->_start();
        }
    }

    private function parseArgv()
    {
        global $argv;
        $this->argv = $argv;
        if (count($this->argv) < 2) {
            Log::DumpExit('Parameter missed');
        }

        [
            $this->entryFile,
            $this->action,
        ] = $argv;

        $this->application = $argv[2] ?? 'server';
        $this->env = $argv[3] ?? 'Dev';
        $this->isDebug = isset($argv[4]) && 'true' === trim($argv[4]) ? true : false;
    }

    private function checkStartEnv()
    {
        if (php_sapi_name() != "cli") {
            Log::DumpExit("Arrow hint : only run in command line mode");
        }
    }


    private function _checkExtension()
    {
        if (!extension_loaded('swoole')) {
            Log::DumpExit('extension swoole is not installed/loaded.');
        }

        if (!extension_loaded('sysvmsg')) {
            Log::DumpExit('extension sysvmsg is not installed/loaded.');
        }

        if ((int)str_replace('.', '', (new \ReflectionExtension('swoole'))->getVersion()) < 400) {
            Log::DumpExit('swoole version must be newer than 4.0 .');
        }

    }


    public function IsDebug()
    {
        return $this->isDebug;
    }

    public function GetEnv()
    {
        return ucfirst($this->env);
    }
}
