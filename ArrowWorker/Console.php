<?php

namespace ArrowWorker;

use ArrowWorker\Library\Process;
use ArrowWorker\Library\System\LoadAverage;
use ArrowWorker\Log\Log;

/**
 * Class Console
 * @package ArrowWorker
 */
class Console
{

    /**
     * @var Container $container
     */
    private Container $container;

    /**
     * @var array
     */
    private array $argv = [];

    /**
     * @var string
     */
    private string $entryFile = '';

    /**
     * @var string
     */
    private string $runType = '';

    /**
     * @var string
     */
    private string $runEnv = '';

    /**
     * @var string
     */
    private string $actionName = 'start';

    /**
     * @var bool
     */
    private bool $isDebug = false;

    /**
     * @var array
     */
    private array $actionAlias = [
        'stop'    => 'stop',
        'start'   => 'start',
        'status'  => 'getStatus',
        'restart' => 'restart'
    ];

    /**
     * @var array $toBeCheckedExtensionList
     */
    private array $toBeCheckedExtensionList = ['swoole' => 450, 'sysvmsg' => 700];


    /**
     * Console constructor.
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->checkStartEnv();
        $this->parseCommandArgv();
        $this->checkExtension();
        Environment::setType($this->getEnv());
        $this->container->get(Config::class);
    }

    /**
     * @return bool
     */
    private function stop(): bool
    {
        $pid = Daemon::GetPid();
        if (0 === $pid) {
            Log::Hint('Arrow is not running.');
            return false;
        }

        for ($i = 1; $i > 0; $i++) {
            if ($i === 1) {
                if (Process::kill($pid, SIGTERM)) {
                    echo('Arrow stopping');
                } else {
                    Log::Hint('Arrow is not running.');
                    return false;
                }
            } else {
                if (!Process::kill($pid, SIGTERM, true)) {
                    Log::Hint('stopped successfully.');
                    return true;
                }

                echo '.';
                sleep(1);

            }
        }
        return false;
    }

    /**
     *
     * @return void
     */
    public function run(): void
    {
        $action = $this->actionAlias[$this->actionName] ?? null;
        if (is_null($action)) {
            Log::Hint("Oops! Unknown operation. please use \"php {$this->entryFile} start/stop/status/restart\" to start/stop/restart the service");
            return;
        }

        $this->$action();
    }

    /**
     *
     */
    private function start(): void
    {
        Log::Hint("starting ...{$this->runType}({$this->runEnv})");
        $this->container->get(App::class, [$this->container])->run($this->runType, $this->isDebug);
    }

    /**
     *
     */
    private function getStatus(): void
    {
        $keyword = PHP_OS === 'Darwin' ? $this->entryFile : Daemon::APP_NAME . '_' . Daemon::GetPid();
        $commend = "ps -e -o 'user,pid,ppid,pcpu,%mem,args' | grep {$keyword}";
        $output  = 'user | pid | ppid | cpu usage | memory usage | process name' . PHP_EOL;
        $results = LoadAverage::Exec($commend);
        $output  .= implode(PHP_EOL, $results);
        echo $output . PHP_EOL;
    }

    /**
     *
     */
    private function restart(): void
    {
        if ($this->stop()) {
            $this->start();
        }
    }

    /**
     *
     */
    private function parseCommandArgv(): void
    {
        global $argv;
        $this->argv = $argv;
        if (count($argv) < 2) {
            Log::DumpExit('Parameter needed');
        }

        [
            $this->entryFile,
            $this->actionName,
        ] = $argv;

        $this->runEnv  = $argv[3] ?? 'dev';
        $this->runType = $argv[2] ?? 'server';
        $this->isDebug = isset($argv[4]) && 'true' === trim($argv[4]);
    }

    /**
     *
     */
    private function checkStartEnv(): void
    {
        if (PHP_SAPI !== "cli") {
            Log::DumpExit("Arrow hint : only run in command line mode");
        }
    }

    /**
     *
     */
    private function checkExtension(): void
    {
        $toBeCheckedExtensionList = $this->toBeCheckedExtensionList;
        foreach ($toBeCheckedExtensionList as $extensionName => $version) {
            if (!extension_loaded($extensionName)) {
                Log::DumpExit("extension {$extensionName} is not installed/loaded.");
            }

            if ((int)str_replace('.', '', (new \ReflectionExtension($extensionName))->getVersion()) < (int)$version) {
                Log::DumpExit("{$extensionName} version must be newer than {$version}");
            }
        }
    }

    /**
     * @return bool
     */
    public function isDebug(): bool
    {
        return $this->isDebug;
    }

    /**
     * @return string
     */
    public function getEnv(): string
    {
        return ucfirst($this->runEnv);
    }

}
