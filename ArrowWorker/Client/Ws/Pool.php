<?php
/**
 * By yubin at 2019-10-05 11:07.
 */

namespace ArrowWorker\Client\Ws;

use ArrowWorker\Config;
use ArrowWorker\Container;
use ArrowWorker\Library\Channel as SwChan;
use ArrowWorker\Library\Context;
use ArrowWorker\Log\Log;
use ArrowWorker\PoolExtend;
use ArrowWorker\PoolInterface as ConnPool;

class Pool extends PoolExtend implements ConnPool
{

    const LOG_NAME = 'WsClient';

    const CONFIG_NAME = 'WsClient';

    const MODULE_NAME = 'WsPool';


    public function __construct(Container $container, array $presetConfig, array $userConfig = [])
    {
        self::$instance = $this;
        $this->container = $container;
        $this->initConfig($presetConfig, $userConfig);
        $this->initPool();
    }

    /**
     * @param array $presetConfig specified keys and pool size
     * @param array $userConfig
     */
    private function initConfig(array $presetConfig, array $userConfig = [])
    {
        if (count($userConfig) > 0) {
            $config = $userConfig;
            goto INIT;
        }

        $config = Config::Get(self::CONFIG_NAME);
        if (!is_array($config) || count($config) == 0) {
            Log::Dump('incorrect config file', Log::TYPE_WARNING, self::MODULE_NAME);
            return;
        }

        INIT:
        foreach ($config as $index => $value) {
            if (!isset($presetConfig[$index])) {
                continue;
            }

            if (
                !isset($value['host']) ||
                !isset($value['port']) ||
                !isset($value['uri']) ||
                !isset($value['isSsl'])
            ) {
                Log::Dump("configuration for {$index} is incorrect. config : " .
                    json_encode($value), Log::TYPE_WARNING, self::MODULE_NAME);
                continue;
            }

            $value['poolSize'] = (int)$presetConfig[$index] >
            0 ? $presetConfig[$index] : self::DEFAULT_POOL_SIZE;
            $value['connectedNum'] = 0;

            $this->config[$index] = $value;
            $this->pool[$index] = $this->container->Make(SwChan::class, [
                $this->container,
                $value['poolSize'],
            ]);
        }
    }


    /**
     * initialize connection pool
     */
    public function InitPool()
    {
        foreach ($this->config as $index => $config) {
            for ($i = $config['connectedNum']; $i < $config['poolSize']; $i++) {
                $wsClient = $this->container->Make(
                    Client::class,
                    [
                        $config['host'],
                        $config['port'],
                        $config['uri'],
                        $config['isSsl'],
                    ]
                );
                $upgrade = $wsClient->Upgrade();
                if (false === $upgrade) {
                    Log::Dump("initialize connection failed, config : {$index}=>" .
                        json_encode($config), Log::TYPE_WARNING, self::MODULE_NAME);
                    continue;
                }
                $this->config[$index]['connectedNum']++;
                $this->pool[$index]->Push($wsClient);
            }
        }
    }

    /**
     * @param string $alias
     * @return false|Client
     */
    public static function Get($alias = 'default')
    {
        $class = __CLASS__;
        $conn = Context::GetSub($class, $alias);
        if (!is_null($conn)) {
            return $conn;
        }

        return self::$instance->GetConnection($class, $alias);;
    }

    public function getConnection(string $alias)
    {
        if (!isset($pool->pool[$alias])) {
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
                Log::Critical("get ( {alias} : {retryTimes} ) connection failed, retrying", [
                    'alias'      => $alias,
                    'retryTimes' => $retryTimes,
                ], self::LOG_NAME);
                goto _RETRY;
            }
        }

        Context::SetSub(__CLASS__, $alias, $conn);
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