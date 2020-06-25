<?php
/**
 * User: Louis
 * Date: 2016/8/1 19:47
 */

namespace ArrowWorker;

/**
 * Class App
 * @package ArrowWorker
 */
class App
{

    /**
     *
     */
    const TYPE_HTTP = 1;

    /**
     *
     */
    const TYPE_WEBSOCKET = 2;

    /**
     *
     */
    const TYPE_TCP = 3;

    /**
     *
     */
    const TYPE_UDP = 4;

    /**
     *
     */
    const TYPE_BASE = 5;

    /**
     *
     */
    const TYPE_WORKER = 6;

    const ENV_DEV = 'Dev';

    const ENV_TEST = 'Test';

    const ENV_PRODUCTION = 'Production';

    /**
     * @var Container
     */
    private $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function Run(string $appType, bool $isDebug=true)
    {
        $this->initOptions();
        $this->runDaemon($appType, $isDebug);
    }

    public function runDaemon(string $appType, bool $isDebug=true)
    {
        $this->container->Get(Daemon::class, [
            $this->container,
            $appType,
            $isDebug,
        ])->Start();
    }

    public function initOptions()
    {
        $options = Config::Get('Global');
        if (!is_array($options)) {
            return;
        }

        foreach ($options as $option => $value) {
            if (false === ini_set($option, $value)) {
                Log::Hint("ini_set({$option}, {$value}) failed.");
            }
        }
        Exception::Init();
    }


}
