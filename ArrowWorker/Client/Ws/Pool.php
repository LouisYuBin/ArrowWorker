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
use ArrowWorker\Std\Pool\PoolCommon;
use ArrowWorker\Std\Pool\PoolInterface as ConnPool;

class Pool extends PoolCommon implements ConnPool
{

    const CONFIG_NAME = 'WsClient';

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

        $config = Config::get(self::CONFIG_NAME);
        if (!is_array($config) || count($config) == 0) {
            Log::Dump('incorrect config file', Log::TYPE_WARNING, __METHOD__);
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
                    json_encode($value), Log::TYPE_WARNING, __METHOD__);
                continue;
            }

            $value['poolSize'] = (int)($presetConfig[$index] ?? self::DEFAULT_POOL_SIZE);
            $value['connectedCount'] = 0;

            $this->config[$index] = $value;
            $this->pool[$index] = $this->container->make(SwChan::class, [
                $this->container,
                $value['poolSize'],
            ]);
        }
    }


    /**
     *
     * @param string $targetAlias
     */
    private function initConnection(string $targetAlias)
    {
        foreach ($this->config as $alias => $config) {
            if($targetAlias!==$alias) {
                continue;
            }

            $client = $this->container->make(
                Client::class,
                [
                    $config['host'],
                    $config['port'],
                    $config['uri'],
                    $config['isSsl'],
                ]
            );
            $upgrade = $client->Upgrade();
            if (false === $upgrade) {
                Log::Dump("initialize connection failed, config : {$alias}=>" .
                    json_encode($config), Log::TYPE_WARNING, __METHOD__);
                continue;
            }
            $this->config[$alias]['connectedCount']++;
            $this->pool[$alias]->Push($client);
        }
    }

    /**
     * @param string $alias
     * @return false|Client
     */
    public static function Get($alias = 'default')
    {
        $class = __CLASS__;
        $conn = Context::getSub($class, $alias);
        if (!is_null($conn)) {
            return $conn;
        }

        return self::$instance->GetConnection($alias);;
    }

    public function getConnection(string $alias)
    {
        if (!isset($pool->pool[$alias])) {
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
                Log::warning("get ( {alias} : {retryTimes} ) connection failed, retrying", [
                    'alias'      => $alias,
                    'retryTimes' => $retryTimes,
                ], __METHOD__);
                goto RETRY;
            }
        }

        Context::subSet(__CLASS__, $alias, $conn);
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