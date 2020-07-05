<?php
/**
 * By yubin at 2020/7/3
 */

namespace ArrowWorker\Log\Process;

use ArrowWorker\Chan;
use ArrowWorker\Client\Tcp\Client as Tcp;
use ArrowWorker\Component\Cache\Redis;
use ArrowWorker\Component\Channel\Queue;
use ArrowWorker\Config;
use ArrowWorker\Console;
use ArrowWorker\Container;
use ArrowWorker\Library\Channel;
use ArrowWorker\Library\Coroutine;
use ArrowWorker\Log\Log;

class Process
{

    /**
     * write log to file
     * @var string
     */
    const TO_FILE = 'file';

    /**
     * write log to redis queue
     * @var string
     */
    const TO_REDIS = 'redis';

    /**
     * write log to tcp server
     * @var string
     */
    const TO_TCP = 'tcp';


    const MAX_BUFFER_SIZE = 4096;

    /**
     * tcp client heartbeat period
     */
    const TCP_HEARTBEAT_PERIOD = 30;

    /**
     *
     */
    const LOG_NAME = 'Log';

    /**
     *
     */
    const MODULE_NAME = 'Log';

    /**
     * @var int
     */
    const  CHAN_SIZE = 204800;

    /**
     * @var Container $container
     */
    private Container $container;


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
     * directory for store log files
     * @var string
     */
    private string $baseDir = APP_PATH . DIRECTORY_SEPARATOR . APP_RUNTIME_DIR . '/Log/';

    /**
     * write log to file
     * @var array
     */
    private array $toTypes = [
        'file',
    ];

    /**
     * password of redis
     * @var array
     */
    private array $tcpConfig = [
        'host' => '127.0.0.1',
        'port' => '6379',
    ];

    /**
     * @var array
     */
    private array $redisConfig = [
        'host'     => '127.0.0.1',
        'port'     => '6379',
        'queue'    => 'ArrowLog',
        'password' => '',
    ];

    /**
     * @var
     */
    private string $stdoutFile;

    /**
     * @var Queue
     */
    private Queue $msgInstance;

    private int $processNum = 1;

    /**
     * Whether to close the log process
     * @var bool
     */
    private bool $isTerminate = false;

    /**
     * Whether to close the log channel
     * @var bool
     */
    private bool $isChanTerminate = false;

    /**
     *
     * @var array
     */
    private array $tcpClient = [];

    /**
     * redis instance
     * @var array
     */
    private array $redisClient = [];

    /**
     * @var Channel;
     */
    private Channel $toFileChan;

    /**
     * @var Channel
     */
    private Channel $toTcpChan;

    /**
     * @var Channel
     */
    private Channel $toRedisChan;

    /**
     * @var array
     */
    private array $fileHandlerMap = [];

    public function __construct(Container $container, array $config)
    {
        $this->container = $container;
        $this->initConfig($config);
        $this->initDirectory();
        $this->initMsgInstance();
        $this->resetStd();
    }

    private function initDirectory()
    {
        if (!is_dir($this->baseDir)) {
            if (!mkdir($this->baseDir, 0777, true)) {
                Log::DumpExit('creating log directory failed.');
            }
        }
    }

    private function initConfig(array $config)
    {
        $this->toTypes = [self::TO_FILE];
        $toTcp         = self::TO_TCP;
        if (isset(
            $config[$toTcp],
            $config[$toTcp]['host'],
            $config[$toTcp]['port']
        )) {
            $config[$toTcp]['poolSize'] = $config[$toTcp]['poolSize'] ?? 10;
            $this->toTypes[]            = $toTcp;
            $this->tcpConfig            = $config[$toTcp];
        }

        $toRedis = self::TO_REDIS;
        if (isset(
            $config[$toRedis],
            $config[$toRedis]['host'],
            $config[$toRedis]['port'],
            $config[$toRedis]['password'],
            $config[$toRedis]['queue']
        )) {
            $config[$toRedis]['poolSize'] = $config[$toRedis]['poolSize'] ?? 10;
            $this->toTypes[]              = $toRedis;
            $this->redisConfig            = $config[$toRedis];
        }

        $this->bufSize    = $config['bufSize'] ?? $this->bufSize;
        $this->baseDir    = $config['baseDir'] ?? $this->baseDir;
        $this->stdoutFile = $this->baseDir . DIRECTORY_SEPARATOR . 'Arrow.log';
        $this->processNum = $config['process'] ?? 1;
    }


    private function initHandler()
    {
        $this->toFileChan = $this->container->Make(Channel::class, [$this->container, self::CHAN_SIZE]);

        foreach ($this->toTypes as $type) {
            switch ($type) {
                case self::TO_REDIS:

                    $config = $this->redisConfig;
                    for ($i = 0; $i < $config['poolSize']; $i++) {
                        $client = $this->container->Make(Redis::class, [$this->container, [
                            'host'     => $config['host'],
                            'port'     => $config['port'],
                            'password' => $config['password'],
                        ]]);
                        if ($client->InitConnection()) {
                            $this->redisClient[] = $client;
                        } else {
                            if (0 == $i) {
                                Log::Dump('init redis client failed, config : ' .
                                    json_encode($config), Log::TYPE_WARNING, __METHOD__);
                            }
                        }
                    }
                    $this->toRedisChan = $this->container->Make(Channel::class, [$this->container, self::CHAN_SIZE]);

                    break;

                case self::TO_TCP;

                    $this->toTcpChan = $this->container->Make(Channel::class, [$this->container, self::CHAN_SIZE]);
                    $config          = $this->tcpConfig;
                    for ($i = 0; $i < $config['poolSize']; $i++) {
                        $client = $this->container->Make(Tcp::class, [$config['host'], $config['port']]);
                        if ($client->IsConnected()) {
                            $this->tcpClient[] = $client;
                        } else {
                            if (0 == $i) {
                                Log::Dump('init tcp client failed. config : ' .
                                    json_encode($config), Log::TYPE_WARNING, __METHOD__);
                            }
                        }
                    }

                    break;

                default:
                    // nothing need to be done

            }
        }

    }

    /**
     * _selectLogChan : select the log chan
     * @return void
     */
    private function initMsgInstance()
    {
        $this->msgInstance = Chan::Get(
            'log',
            [
                'msgSize' => $this->msgSize,
                'bufSize' => $this->bufSize,
            ]
        );
    }

    /**
     * @param string $module
     * @param string $level
     * @param string $log
     * @param string $date
     * @return void
     */
    private function writeFile(string $module, string $level, string $log, string $date)
    {
        $alias = "{$module}{$level}{$date}";

        CHECK_FILE_HANDLER:
        if (isset($this->fileHandlerMap[$alias])) {
            goto WRITE_LOG;
        }

        $fileRes = $this->initFileHandler($module, $this->getFileName($level, $date));
        if (false === $fileRes) {
            goto CHECK_FILE_HANDLER;
        }
        $this->fileHandlerMap[$alias] = $fileRes;

        WRITE_LOG:
        $result = Coroutine::FileWrite($this->fileHandlerMap[$alias], $log);
        if (false === $result) {
            Log::Dump("Coroutine::FileWrite failed, log : {$log}", Log::TYPE_EMERGENCY, __METHOD__);
        }

    }

    /**
     * @param string $fileDir
     * @param string $fileExt
     * @return bool|resource
     */
    private function initFileHandler(string $fileDir, string $fileExt)
    {
        $fileDir  = $this->baseDir . $fileDir;
        $filePath = "{$fileDir}/{$fileExt}";

        $initDirectoryTimes = 0;
        RE_CHECK_DIR:
        if (!is_dir($fileDir)) {
            $initDirectoryTimes++;
            if (!mkdir($fileDir, 0766, true)) {
                if ($initDirectoryTimes > 2) {
                    Log::Dump("make log directory:{$fileDir} failed", Log::TYPE_EMERGENCY, __METHOD__);
                    return false;
                }
                Coroutine::Sleep(0.5);
                goto RE_CHECK_DIR;
            }
        }

        $fileRes = fopen($filePath, 'a');
        if (false === $fileRes) {
            Log::Dump("fopen log file:{$filePath} failed", Log::TYPE_EMERGENCY, __METHOD__);
            return false;
        }
        return $fileRes;
    }


    /**
     * @param string $level
     * @param string $date
     * @return string
     */
    private function getFileName(string $level, string $date)
    {
        switch ($level) {
            case 'A':
                $ext = "Alert";
                break;
            case 'D':
                $ext = "Debug";
                break;
            case 'E':
                $ext = "Error";
                break;
            case 'W':
                $ext = "Warning";
                break;
            case 'N':
                $ext = "Notice";
                break;
            case 'C':
                $ext = "Critical";
                break;
            case 'EM':
                $ext = "Emergency";
                break;
            default:
                $ext = "Info";
        }
        return "{$date}.{$ext}.log";
    }

    /**
     * Start : start log process
     */
    public function Start()
    {
        $this->initHandler();
        $this->initSignalHandler();
        $this->initCoroutine();
        $this->exit();
    }

    private function initCoroutine()
    {
        Coroutine::Enable();
        for ($i = 0; $i < 64; $i++) {
            Coroutine::Create(function () {
                $this->WriteToFile();
            });
        }

        $tcpClientCount = count($this->tcpClient);
        for ($i = 0; $i < $tcpClientCount; $i++) {
            Coroutine::Create(function () use ($i) {
                $this->WriteToTcp($i);
            });
        }

        $redisClientCount = count($this->redisClient);
        for ($i = 0; $i < $redisClientCount; $i++) {
            Coroutine::Create(function () use ($i) {
                $this->WriteToRedis($i);
            });
        }

        for ($i = 0; $i < 2; $i++) {
            Coroutine::Create(function () {
                $this->Dispatch();
            });
        }

        Coroutine::Create(function () {
            while (true) {
                if ($this->isTerminate) {
                    break;
                }
                Coroutine::Sleep(0.2);
                pcntl_signal_dispatch();
            }
        });
        Coroutine::Wait();
    }

    public function Dispatch()
    {
        $msgQueue = $this->msgInstance;
        $toTypes  = $this->toTypes;
        while (true) {
            if (
                $this->isTerminate &&
                $msgQueue->Status()['msg_qnum'] == 0
            ) {
                break;
            }

            $log = $msgQueue->Read(10000);
            if ($log === false) {
                continue;
            }

            if (false == $this->toFileChan->Push($log, 1)) {
                Log::Dump("push log chan failed, data:" . json_encode($log) . ", error code： " .
                    $this->toFileChan->GetErrorCode() .
                    "}", Log::TYPE_WARNING, __METHOD__);
            }

            $log = json_encode($log, JSON_UNESCAPED_UNICODE);
            if (in_array(self::TO_TCP, $toTypes)) {
                $this->toTcpChan->Push($log, 1);
            }

            if (in_array(self::TO_REDIS, $toTypes)) {
                $this->toRedisChan->Push($log, 1);
            }

        }

        $this->isChanTerminate = true;
        //self::Dump( self::MODULE_NAME.'dispatch coroutine exited' );
    }

    /**
     *
     */
    public function WriteToFile()
    {
        $buffer = [];
        $break  = true;
        while (true) {
            $data = $this->toFileChan->Pop(0.2);
            if ($this->isChanTerminate && $data === false && $break) {
                break;
            }

            $date = date('Ymd');
            if ($data === false) {
                goto FLUSH;
            }

            [
                $level,
                $module,
                $time,
                $message,
                $context,
                $id,
            ] = $data;
            $body      = "{$time} | {$id} | " . $this->parseLog($message, $context);
            $bufferKey = $module . $level;
            if (isset($buffer[$bufferKey])) {
                $buffer[$bufferKey]['body'] = $buffer[$bufferKey]['body'] . $body;
                $buffer[$bufferKey]['size'] += strlen($body);
            } else {
                $buffer[$bufferKey] = array_merge(
                    [
                        'body'      => $body,
                        'size'      => strlen($body),
                        'module'    => $module,
                        'level'     => $level,
                        'flushTime' => time(),
                    ]
                );
            }

            FLUSH:
            $emptyBufferCount = 0;
            foreach ($buffer as $eachBufKey => $eachBuffer) {
                if (0 == $eachBuffer['size']) {
                    $emptyBufferCount++;
                    continue;
                }

                if (time() - $eachBuffer['flushTime'] >= 2 || $eachBuffer['size'] >= self::MAX_BUFFER_SIZE) {
                    $this->writeFile($eachBuffer['module'], $eachBuffer['level'], $eachBuffer['body'], $date);
                    $buffer[$eachBufKey]['body']      = '';
                    $buffer[$eachBufKey]['size']      = 0;
                    $buffer[$eachBufKey]['flushTime'] = time();
                }
            }
            $break = count($buffer) == $emptyBufferCount ? true : false;
        }
        //self::Dump( self::MODULE_NAME.'file-writing coroutine exited' );
    }

    private function parseLog(string $message, array $context = []): string
    {
        $replace = [];
        foreach ($context as $key => $val) {
            if (is_string($val)) {
                $replace["{{$key}}"] = $val;
            }
        }
        return strtr($message, $replace) . PHP_EOL;
    }

    /**
     * @var int $clientIndex
     */
    public function WriteToTcp(int $clientIndex)
    {
        while (true) {
            $data = $this->toTcpChan->Pop(0.5);
            if ($this->isChanTerminate && $data === false) {
                break;
            }

            if ($data === false) {
                Coroutine::Sleep(1);
                continue;
            }

            if (false == $this->tcpClient[$clientIndex]->Send($data, 3)) {
                Log::Dump(" tcpClient[{$clientIndex}]->Send( {$data}, 3 ) failed", Log::TYPE_WARNING, __METHOD__);
            }
        }
        //self::Dump( self::MODULE_NAME . ' [ Debug ] tcp-writing coroutine exited' );
    }

    /**
     * @var int $clientIndex
     */
    public function WriteToRedis(int $clientIndex)
    {
        $queue = $this->redisConfig['queue'];
        while (true) {
            $data = $this->toRedisChan->Pop(0.5);
            if ($this->isChanTerminate && $data === false) {
                break;
            }

            if ($data === false) {
                Coroutine::Sleep(1);
                continue;
            }

            for ($i = 0; $i < 3; $i++) {
                if (false !== $this->redisClient[$clientIndex]->Lpush($queue, $data)) {
                    break;
                }
                Log::Dump("redisClient[{$clientIndex}]->Lpush( {$queue}, {$data} ) failed", Log::TYPE_WARNING, __METHOD__);
            }

        }

    }

    /**
     * exit : exit log process while there are no message in log queue
     */
    private function exit()
    {
        Log::Dump(' exited. queue status : ' .
            json_encode($this->msgInstance->Status()), Log::TYPE_DEBUG, __METHOD__);
        exit(0);
    }

    private function resetStd()
    {
        if ($this->container->Get(Console::class)->IsDebug()) {
            return;
        }

        global $STDOUT, $STDERR;
        $newStdResource = fopen($this->stdoutFile, "a");
        if (!is_resource($newStdResource)) {
            die("ArrowWorker hint : can not open stdoutFile" . PHP_EOL);
        }

        fclose(STDOUT);
        fclose(STDERR);
        $STDOUT = fopen($this->stdoutFile, 'a');
        $STDERR = fopen($this->stdoutFile, 'a');
    }

    /**
     * _setSignalHandler : set function for signal handler
     * @author Louis
     */
    private function initSignalHandler()
    {
        pcntl_signal(SIGALRM, [
            $this,
            "SignalHandler",
        ], false);
        pcntl_signal(SIGTERM, [
            $this,
            "SignalHandler",
        ], false);

        pcntl_signal(SIGCHLD, SIG_IGN, false);
        pcntl_signal(SIGQUIT, SIG_IGN, false);

        pcntl_alarm(self::TCP_HEARTBEAT_PERIOD);
    }


    /**
     * signalHandler : function for handle signal
     * @param int $signal
     * @author Louis
     */
    public function SignalHandler(int $signal)
    {
        switch ($signal) {
            case SIGALRM:
                $this->handleAlarm();
                break;
            case SIGTERM:
                $this->isTerminate = true;
                break;
            default:
        }
    }

    /**
     * handle log process alarm signal
     */
    private function handleAlarm()
    {
        $this->sendTcpHeartbeat();
        $this->cleanUselessFileHandler();
        pcntl_alarm(self::TCP_HEARTBEAT_PERIOD);
    }

    /**
     *
     */
    private function cleanUselessFileHandler()
    {
        $time = (int)date('Hi');
        if ($time > 2) {
            return;
        }

        Log::InitId();
        $today = date('Ymd');
        foreach ($this->fileHandlerMap as $alias => $handler) {
            $aliasDate = substr($alias, strlen($alias) - 8, 8);
            if ($today != $aliasDate) {
                fclose($this->fileHandlerMap[$alias]);
                unset($this->fileHandlerMap[$alias]);
                Log::Debug("log file handler : {$alias} was cleaned.", [], self::LOG_NAME);
            }
        }
    }

    /**
     *
     */
    private function sendTcpHeartbeat()
    {
        foreach ($this->tcpClient as $client) {
            $client->Send('heartbeat');
        }
    }
}