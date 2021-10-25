<?php
/**
 * User: louis
 * Time: 18-6-10 上午1:16
 */

namespace ArrowWorker\Log;


use ArrowWorker\Chan;
use ArrowWorker\Component\Channel\Queue;
use ArrowWorker\Config;
use ArrowWorker\Container;
use ArrowWorker\Library\Context;
use ArrowWorker\Library\Coroutine;
use ArrowWorker\Library\Process;
use ArrowWorker\Log\Process\Process as LogProcess;

/**
 * Class Log
 * @package ArrowWorker
 */
class Log
{

    /**
     *
     */
    public const TYPE_WARNING = 'Warning';

    /**
     *
     */
    public const TYPE_NOTICE = 'Notice';

    /**
     *
     */
    public const TYPE_DEBUG = 'Debug';

    /**
     *
     */
    public const TYPE_ERROR = 'Error';

    /**
     *
     */
    public const TYPE_EMERGENCY = 'Emergency';

    /**
     *
     */
    public const TYPE_EXCEPTION = 'Exception';


    /**
     * bufSize : log buffer size 10M
     * @var int
     */
    private int $bufSize = 10485760;

    /**
     * msgSize : a single log size 1M
     * @var int
     */
    private int $msgSize = 1048576;


    /**
     *
     */
    const MODULE_NAME = 'Log';

    /**
     * directory for store log files
     * @var string
     */
    private string $baseDir = APP_PATH . DIRECTORY_SEPARATOR . APP_RUNTIME_DIR . '/Log/';


    /**
     * @var
     */
    private string $stdoutFile;

    /**
     * @var array
     */
    private array $config = [];


    /**
     * @var Queue
     */
    private Queue $msgInstance;

    /**
     * @var LogProcess|mixed
     */
    private LogProcess $process;

    /**
     * @var int
     */
    private int $processNum = 1;

    /**
     * Log constructor.
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->stdoutFile = $this->baseDir . DIRECTORY_SEPARATOR . 'Arrow.log';
        $this->initConfig();
        $this->initMsgInstance();
        $this->process = $container->Make(LogProcess::class, [$container, $this->config]);
    }

    /**
     *
     */
    private function initConfig()
    {
        $config = Config::get('Log');
        if (false === $config || !is_array($config)) {
            return;
        }
        $this->config = $config;
    }

    /**
     * @return LogProcess
     */
    public function getProcess(): LogProcess
    {
        return $this->process;
    }

    /**
     * _selectLogChan : select the log chan
     * @return void
     */
    private function initMsgInstance()
    {
        $this->msgInstance = Chan::get(
            'log',
            [
                'msgSize' => $this->msgSize,
                'bufSize' => $this->bufSize,
            ]
        );
    }


    /**
     * Info write an information log
     * @param string $log
     * @param array $context
     * @param string $module
     * @return void
     */
    public static function info(string $log, array $context = [], string $module = ''): void
    {
        self::rebuildLog($log, $context, $module, 'I');
    }

    /**
     * Info write an information log
     * @param string $log
     * @param array $context
     * @param string $module
     * @return void
     */
    public static function alert(string $log, array $context = [], string $module = ''): void
    {
        self::rebuildLog($log, $context, $module, 'A');
    }

    /**
     * @param string $log
     * @param array $context
     * @param string $module
     * @return void
     */
    public static function debug(string $log, array $context = [], string $module = ''): void
    {
        self::rebuildLog($log, $context, $module, 'D');
    }

    /**
     * Notice : write an notice log
     * @param string $log
     * @param array $context
     * @param string $module
     * @return void
     */
    public static function notice(string $log, array $context = [], string $module = ''): void
    {
        self::rebuildLog($log, $context, $module, 'N');
    }

    /**
     * Warning : write an warning log
     * @param string $log
     * @param array $context
     * @param string $module
     * @return void
     */
    public static function warning(string $log, array $context = [], string $module = ''): void
    {
        self::rebuildLog($log, $context, $module, 'W');
    }

    /**
     * Error : write an error log
     * @param string $log
     * @param array $context
     * @param string $module
     * @return void
     */
    public static function error(string $log, array $context = [], string $module = ''): void
    {
        self::rebuildLog($log, $context, $module, 'E');
    }

    /**
     * Emergency : write an Emergency log
     * @param string $log
     * @param array $context
     * @param string $module
     * @return void
     */
    public static function emergency(string $log, array $context = [], string $module = ''): void
    {
        self::rebuildLog($log, $context, $module, 'EM');
    }

    /**
     * Critical : write a Critical log
     * @param string $log
     * @param array $context
     * @param string $module
     * @return void
     */
    public static function critical(string $log, array $context = [], string $module = ''): void
    {
        self::Dump($log, self::TYPE_EMERGENCY, $module);
        self::rebuildLog($log, $context, $module, 'C');
    }

    /**
     * @param string $log
     * @param array $context
     * @param string $module
     * @param string $level
     */
    private static function rebuildLog(string $log, array $context = [], string $module = '', string $level = 'D'): void
    {
        Context::fill(__CLASS__, [
            $level,
            $module,
            date('Y-m-d H:i:s'),
            $log,
            $context,
        ]);
    }

    /**
     * Dump : echo log to standard output
     * @param string $log
     * @param string $type
     * @param string $module
     */
    public static function Dump(string $log, string $type = self::TYPE_DEBUG, string $module = 'Unknown')
    {
        echo sprintf("%s | %s | %s | %s " . PHP_EOL, self::getTime(), $type, $module, $log);
    }

    /**
     * @return false|string
     */
    private static function getTime()
    {
        return date('Y-m-d H:i:s');
    }

    /**
     * Dump : echo log to standard output
     * @param string $log
     */
    public static function DumpExit(string $log)
    {
        echo(PHP_EOL . static::getTime() . ' ' . $log . PHP_EOL);
        exit(0);
    }

    /**
     * @param string $log
     */
    public static function Hint(string $log)
    {
        echo $log . PHP_EOL;
    }

    /**
     * @param string $logId
     */
    public static function initId(string $logId = ''): void
    {

        Context::set(
            __CLASS__ . '_id',
            '' === $logId ?
                date('ymdHis') . Process::id() . Coroutine::id() . random_int(100, 999) :
                $logId
        );
    }

    /**
     * @return string
     */
    public static function GetLogId(): string
    {
        return Context::get(__CLASS__ . '_id');
    }

    /**
     *
     */
    public function Release()
    {
        $class = __CLASS__;
        $logs  = Context::get($class);
        if (is_null($logs)) {
            return;
        }

        $logId = Context::get($class . '_id');
        foreach ($logs as $log) {
            $log[5] = $logId;
            $this->msgInstance->write($log);
        }
    }


    /**
     * @return string
     */
    public function GetStdOutFilePath()
    {
        return $this->stdoutFile;
    }

    /**
     * @return int
     */
    public function GetProcessNum(): int
    {
        return $this->processNum;
    }


}