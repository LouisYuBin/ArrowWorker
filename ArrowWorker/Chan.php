<?php

namespace ArrowWorker;

use ArrowWorker\Component\Channel\Queue;

/**
 * Class Message
 */
class Chan
{

    /**
     * channel config file name
     */
    const CONFIG_NAME = 'Chan';

    const MODULE_NAME = 'Chan';

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
    protected $alias = [];

    /**
     * channel pool
     * @var Container $container
     */
    protected $container;

    protected $class;

    private static $instance;

    public function __construct(Container $container)
    {
        self::$instance = $this;
        $this->class = __CLASS__;
        $this->container = $container;
    }

    /**
     * initialize channel and return channel object
     * @param string $alias
     * @param array $userConfig
     * @return Queue|bool
     * @author Louis
     */
    public static function Get(string $alias = 'default', array $userConfig = [])
    {
        /**
         * @var Chan $channels
         */
        $channels = self::$instance;
        if (isset($channels->alias[$alias])) {
            return $channels->alias[$alias];  //channel is already been initialized
        }
        return $channels->initQueue($alias, $userConfig);
    }

    private function initQueue(string $alias, array $userConfig)
    {
        if (0 == count($userConfig)) {
            $configs = Config::Get(self::CONFIG_NAME);
            if (isset($configs[$alias]) && is_array($configs[$alias])) {
                $userConfig = $configs[$alias];
            } else {
                Log::Dump("{$alias} config does not exists/is not array.", Log::TYPE_WARNING, self::MODULE_NAME);
                return false;
            }
        }

        /**
         * @var Queue $queue
         */
        $this->alias[$alias] = $queue = $this->container->Make(Queue::class, [
            array_merge(self::DEFAULT_CONFIG, $userConfig),
            $alias,
        ]);

        return $queue;
    }

    /**
     * Close 关闭管道
     * @author Louis
     */
    public static function Close()
    {
        $channels = self::$instance;
        foreach ($channels->alias as $eachQueue) {
            Log::Dump("msg_remove_queue result : " . $eachQueue->Close(), Log::TYPE_DEBUG, self::MODULE_NAME);
        }
    }

}