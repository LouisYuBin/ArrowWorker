<?php
/**
 * User: Louis
 * Date: 2016/8/1 19:47
 */

namespace ArrowWorker;

use ArrowWorker\Log\Log;

/**
 * Class App
 * @package ArrowWorker
 */
class App
{

    /**
     *
     */
    public const TYPE_HTTP = 1;

    /**
     *
     */
    public const TYPE_WEBSOCKET = 2;

    /**
     *
     */
    public const TYPE_TCP = 3;

    /**
     *
     */
    public const TYPE_UDP = 4;

    /**
     *
     */
    public const TYPE_BASE = 5;

    /**
     *
     */
    public const TYPE_WORKER = 6;

    /**
     * @var Container
     */
    private $container;

    /**
     * App constructor.
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * @param string $appType
     * @param bool $isDebug
     */
    public function run(string $appType, bool $isDebug = true): void
    {
        $this->initOptions();
        $this->runDaemon($appType, $isDebug);
    }

    /**
     * @param string $appType
     * @param bool $isDebug
     */
    public function runDaemon(string $appType, bool $isDebug = true): void
    {
        $this->container->get(Daemon::class, [
            $this->container,
            $appType,
            $isDebug,
        ])->start();
    }

    /**
     *
     */
    public function initOptions(): void
    {
        $options = Config::get('Global');
        if (!is_array($options)) {
            return;
        }

        foreach ($options as $option => $value) {
            if (false === ini_set($option, $value)) {
                Log::Hint("ini_set({$option}, {$value}) failed.");
            }
        }
        Exception::init();
    }

    /**
     * @return string
     */
    public function getDirName(): string
    {
        return APP_DIR;
    }


}
