<?php

namespace ArrowWorker\Component\Cache;

use ArrowWorker\Config;
use ArrowWorker\Container;
use ArrowWorker\Library\Channel as SwChan;
use ArrowWorker\Library\Context;
use ArrowWorker\Log;
use ArrowWorker\PoolExtend;
use ArrowWorker\PoolInterface as ConnPool;

class Pool extends PoolExtend implements ConnPool
{

    const LOG_NAME = 'Cache';

    const MODULE_NAME = "CachePool";


    const CONFIG_NAME = 'Cache';


    const DEFAULT_DRIVER = 'Redis';

    private $drivers = [
        'REDIS' => Redis::class,
    ];

    public function __construct(Container $container, array $poolSize, array $config = [])
    {
        self::$instance = $this;
        $this->container = $container;
        $this->InitConfig($poolSize, $config);
        $this->InitPool();
    }

    /**
     * @param array $poolSize
     * @param array $config
     */
    public function InitConfig(array $poolSize, array $config = [])
    {
        if (count($config) > 0) {
            goto INIT;
        }

        $config = Config::Get(self::CONFIG_NAME);
        if (!is_array($config) || count($config) == 0) {
            Log::Dump('incorrect config file', Log::TYPE_WARNING, self::MODULE_NAME);
            return;
        }

        INIT:
        foreach ($config as $index => $value) {
            if (!isset($poolSize[$index])) {
                continue;
            }

            if (
                !isset($value['driver']) ||
                !isset($this->drivers[strtoupper($value['driver'])]) ||
                !isset($value['host']) ||
                !isset($value['port']) ||
                !isset($value['password'])
            ) {
                Log::Dump(__CLASS__ .
                    '::' .
                    __FUNCTION__ .
                    "incorrect configuration . {$index}=>" .
                    json_encode($value), Log::TYPE_WARNING, self::MODULE_NAME);
                continue;
            }

            $value['driver'] = strtoupper($value['driver']);
            $value['poolSize'] = (int)$poolSize[$index] > 0 ? $poolSize[$index] : self::DEFAULT_POOL_SIZE;
            $value['connectedNum'] = 0;

            $this->config[$index] = $value;
            $this->pool[$index] = $this->container->Make(SwChan::class, [$this->container, $value['poolSize']]);

        }
    }


    /**
     * initialize connection pool
     */
    public function InitPool()
    {
        foreach ($this->config as $index => $config) {
            for ($i = $config['connectedNum']; $i < $config['poolSize']; $i++) {
                $conn = $this->container->Make($this->drivers[$config['driver']], [$this->container, $config]);
                if (false === $conn->InitConnection()) {
                    Log::Dump(__CLASS__ .
                        '::' .
                        __FUNCTION__ .
                        " InitConnection failed, config : {$index}=>" .
                        json_encode($config), Log::TYPE_WARNING, self::MODULE_NAME);
                    continue;
                }
                $this->config[$index]['connectedNum']++;
                $this->pool[$index]->Push($conn);
            }
        }
    }

    /**
     * @param string $alias
     * @return false|Redis
     */
    public static function Get(string $alias = 'default')
    {
        $class = __CLASS__;
        $context = Context::GetInstance();
        if (isset($context[$class][$alias])) {
            return $context[$class][$alias];
        }

        return self::$instance->GetConnection($alias);
    }

    public function GetConnection(string $alias)
    {
        $class = __CLASS__;
        if (!isset($this->pool[$alias])) {
            return false;
        }

        $retryTimes = 0;
        _RETRY:
        $conn = $this->pool[$alias]->Pop(0.2);
        if (false === $conn) {
            if ($this->config[$alias]['connectedNum'] < $this->config[$alias]['poolSize']) {
                $this->InitPool();
            }

            if ($retryTimes <= 2) {
                $retryTimes++;
                Log::Dump($class .
                    '::' .
                    __FUNCTION__ .
                    " get connection( {$alias} : {$retryTimes} ) failed,retrying...", Log::TYPE_WARNING, self::MODULE_NAME);
                goto _RETRY;
            }

        }

        Context::SetSub($class, $alias, $conn);
        return $conn;
    }

    /**
     * @return void
     */
    public function Release(): void
    {
        $coConnections = Context::Get(__CLASS__);
        if (is_null($coConnections)) {
            return;
        }

        foreach ($coConnections as $alias => $connection) {
            $this->pool[$alias]->Push($connection);
        }
    }


}