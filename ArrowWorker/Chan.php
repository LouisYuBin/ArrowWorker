<?php

namespace ArrowWorker;

use ArrowWorker\Component\Channel\Queue;
use ArrowWorker\Log\Log;

/**
 * Class Message
 */
class Chan
{

    /**
     * channel config file name
     */
    const CONFIG_NAME = 'Chan';


    /**
     * default config for each channel
     */
    const DEFAULT_CONFIG = [
        'msgSize' => 128,
        'bufSize' => 10240000,
    ];

    /**
     * channel pool
     * @var array
     */
    protected array $alias = [];

    /**
     * channel pool
     * @var Container $container
     */
    protected Container $container;

    /**
     * @var Chan
     */
    private static Chan $instance;

    /**
     * Chan constructor.
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        self::$instance  = $this;
        $this->container = $container;
    }

    /**
     * initialize channel and return channel object
     * @param string $alias
     * @param array $userConfig
     * @return Queue|bool
     * @author Louis
     */
    public static function get(string $alias = 'default', array $userConfig = [])
    {
        $channels = self::$instance;
        if (isset($channels->alias[$alias])) {
            return $channels->alias[$alias];  //channel is already been initialized
        }
        return $channels->initQueue($alias, $userConfig);
    }

    /**
     * @param string $name
     * @param array $userConfig
     * @return Queue|bool
     */
    private function initQueue(string $name, array $userConfig)
    {
        if (empty($userConfig)) {
            $configs = Config::get(self::CONFIG_NAME);
            if (isset($configs[$name]) && is_array($configs[$name])) {
                $userConfig = $configs[$name];
            } else {
                Log::Dump("{$name} config does not exists/is not array.", Log::TYPE_WARNING, __METHOD__);
                return false;
            }
        }

        /**
         * @var Queue $queue
         */
        $this->alias[$name] = $queue = $this->container->make(Queue::class, [
            array_merge(self::DEFAULT_CONFIG, $userConfig),
            $name,
        ]);

        return $queue;
    }

    /**
     * Close 关闭管道
     * @author Louis
     */
    public static function close(): void
    {
        $channels = self::$instance;
        foreach ($channels->alias as $eachQueue) {
            Log::Dump("msg_remove_queue result : " . $eachQueue->close(), Log::TYPE_DEBUG, __METHOD__);
        }
    }

}