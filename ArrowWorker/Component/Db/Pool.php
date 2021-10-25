<?php
/**
 * By yubin at 2019-09-11 10:53.
 */

namespace ArrowWorker\Component\Db;

use ArrowWorker\Config;
use ArrowWorker\Container;
use ArrowWorker\Library\Channel as SwChan;
use ArrowWorker\Library\Context;
use ArrowWorker\Log\Log;
use ArrowWorker\Std\Pool\PoolInterface as ConnPool;


/**
 * Class Pool
 * @package ArrowWorker\Component\Db
 */
class Pool implements ConnPool
{


    /**
     *
     */
    private const CONFIG_NAME = 'Db';


    /**
     * @var array
     */
    private array $drivers = [
        'MYSQLI' => Mysqli::class,
        'PDO'    => Pdo::class,
    ];
    /**
     * @var array
     */
    private array $pool = [];

    /**
     * @var array
     */
    private array $config = [];

    /**
     * @var Container
     */
    private Container $container;

    /**
     * @var Pool
     */
    private static self $instance;

    /**
     * Pool constructor.
     * @param Container $container
     * @param array $presetConfig
     * @param array $userConfig
     */
    public function __construct(Container $container, array $presetConfig, array $userConfig = [])
    {
        self::$instance = $this;
        $this->container = $container;
        $this->initConfig($presetConfig, $userConfig);
    }

    /**
     * @param array $presetConfig
     * @param array $userConfig
     */
    private function initConfig(array $presetConfig, array $userConfig=[])
    {
        if (count($userConfig) > 0) {
            $config = $userConfig;
        } else {
            $config = Config::get(self::CONFIG_NAME);
            if (!is_array($config) || empty($config)) {
                Log::Dump(" incorrect config file", Log::TYPE_WARNING, __METHOD__);
                return;
            }
        }

        foreach ($config as $index => $value) {
            if (!isset($presetConfig[$index])) {
                //initialize specified db config only
                continue;
            }

            //ignore incorrect config
            if (!isset(
                $value['driver'],
                $this->drivers[strtoupper($value['driver'])],
                $value['host'],
                $value['dbName'],
                $value['userName'],
                $value['password'],
                $value['port'],
                $value['charset']
            )) {
                Log::Dump( " incorrect configuration. {$index}=> " . json_encode($value), Log::TYPE_WARNING, __METHOD__);
                continue;
            }

            $value['driver']         = strtoupper($value['driver']);
            $value['poolSize']       = (int)($presetConfig[$index] ?? self::DEFAULT_POOL_SIZE);
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
    public function initConnection(string $targetAlias):void
    {
        foreach ($this->config as $alias => $config) {
            if($targetAlias!==$alias) {
                continue;
            }

            $conn = $this->container->make($this->drivers[$config['driver']], [$this->container, $config]);
            if (false === $conn->InitConnection()) {
                Log::Dump(" {$config['driver']}->InitConnection connection failed, config : {$alias}=>" . json_encode($config), Log::TYPE_WARNING, __METHOD__);
                continue;
            }
            $this->config[$alias]['connectedCount']++;
            $this->pool[$alias]->Push($conn);
        }
    }

    /**
     * @param string $alias
     * @return false|Mysqli|Pdo
     */
    public static function get(string $alias = 'default')
    {
        $className = __CLASS__;
        return Context::getContext()[$className][$alias]??self::$instance->getConnection($className, $alias);
    }

    /**
     * @param string $class
     * @param string $alias
     * @return bool
     */
    private function getConnection(string $class, string $alias) :?bool
    {
        if (!isset($this->pool[$alias])) {
            return false;
        }

        $retryTimes    = 0;
        RETRY:
        $conn = $this->pool[$alias]->Pop(0.2);
        if (false === $conn) {
            if ($this->config[$alias]['connectedCount'] < $this->config[$alias]['poolSize']) {
                $this->initConnection($alias);
                goto RETRY;
            }

            if ($retryTimes <= 2) {
                $retryTimes++;
                Log::Dump( "( {$alias} : {$retryTimes} ) failed, retrying...", Log::TYPE_WARNING, __METHOD__);
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