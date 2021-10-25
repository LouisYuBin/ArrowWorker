<?php

namespace ArrowWorker\Component\Cache;

use ArrowWorker\Config;
use ArrowWorker\Container;
use ArrowWorker\Library\Channel as SwChan;
use ArrowWorker\Library\Context;
use ArrowWorker\Log\Log;
use ArrowWorker\Std\Pool\PoolCommon;
use ArrowWorker\Std\Pool\PoolInterface as ConnPool;

class Pool extends PoolCommon implements ConnPool
{

    const CONFIG_NAME = 'Cache';


    private array $drivers = [
        'REDIS' => Redis::class,
    ];

    public function __construct(Container $container, array $poolSize, array $config = [])
    {
        self::$instance  = $this;
        $this->container = $container;
        $this->initConfig($poolSize, $config);
    }

    /**
     * @param array $poolSize
     * @param array $config
     */
    public function initConfig(array $poolSize, array $config = [])
    {
        if (count($config) > 0) {
            goto INIT;
        }

        $config = Config::get(self::CONFIG_NAME);
        if (!is_array($config) || count($config) == 0) {
            Log::Dump('incorrect config file', Log::TYPE_WARNING, __METHOD__);
            return;
        }

        INIT:
        foreach ($config as $index => $value) {
            if (!isset($poolSize[$index])) {
                continue;
            }

            if (!isset(
                    $value['driver'],
                    $this->drivers[strtoupper($value['driver'])],
                    $value['host'],
                    $value['port'],
                    $value['password']
            )) {
                Log::Dump("incorrect configuration . {$index}=>" .json_encode($value), Log::TYPE_WARNING, __METHOD__);
                continue;
            }

            $value['driver']       = strtoupper($value['driver']);
            $value['poolSize']     = (int)($poolSize[$index] ?? self::DEFAULT_POOL_SIZE);
            $value['connectedCount'] = 0;

            $this->config[$index] = $value;
            $this->pool[$index]   = $this->container->make(SwChan::class, [$this->container, $value['poolSize']]);

        }
    }


    /**
     * initialize connection pool
     *
     * @param string $targetAlias
     */
    public function initConnection(string $targetAlias)
    {
        foreach ($this->config as $alias => $config) {
            if($targetAlias!==$alias) {
                continue;
            }
            $conn = $this->container->make($this->drivers[$config['driver']], [$this->container, $config]);
            if (false === $conn->InitConnection()) {
                Log::Dump("InitConnection failed, config : {$alias}=>" . json_encode($config), Log::TYPE_WARNING, __METHOD__);
                continue;
            }
            $this->config[$alias]['connectedCount']++;
            $this->pool[$alias]->Push($conn);
        }
    }

    /**
     * @param string $alias
     * @return false|Redis
     */
    public static function Get(string $alias = 'default')
    {
        $class = __CLASS__;
        $context = Context::getContext();
        if (isset($context[$class][$alias])) {
            return $context[$class][$alias];
        }

        return self::$instance->GetConnection($class, $alias);
    }

    public function GetConnection(string $class, string $alias)
    {
        if (!isset($this->pool[$alias])) {
            return false;
        }

        $retryTimes = 0;
        RETRY:
        $conn = $this->pool[$alias]->Pop(0.2);
        if (false === $conn) {
            if ($this->config[$alias]['connectedCount'] < $this->config[$alias]['poolSize']) {
                $this->initConnection($alias);
                goto RETRY;
            }

            if ($retryTimes <= 2) {
                $retryTimes++;
                Log::Dump(" get connection( {$alias} : {$retryTimes} ) failed,retrying...", Log::TYPE_WARNING, __METHOD__);
                goto RETRY;
            }
        }

        Context::subSet($class, $alias, $conn);
        return $conn;
    }

    /**
     * @return void
     */
    public function Release(): void
    {
        $coConnections = Context::get(__CLASS__);
        if (is_null($coConnections)) {
            return;
        }

        foreach ($coConnections as $alias => $connection) {
            $this->pool[$alias]->Push($connection);
        }
    }


}