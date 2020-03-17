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

    /**
     *
     */
    const CONTROLLER_NAMESPACE = '\\' . APP_DIR . '\\' . APP_CONTROLLER_DIR . '\\';
    
    /**
     * @var Container
     */
    private $container;
    
    public function __construct(Container $container)
    {
    	$this->container = $container;
    }
    
    public function Run()
    {
	    $console = $this->container->Get(Console::class, [$this->container] );
        $this->initOptions();
        $console->Execute();
    }

    private function initOptions()
    {
        $options = Config::Get('Global');
        if( !is_array($options) )
        {
            return ;
        }

        foreach ($options as $option=>$value)
        {
            if( false===ini_set($option,$value) )
            {
                Log::Hint("ini_set({$option}, {$value}) failed.");
            }
        }
        Exception::Init();
    }

    public static function GetController()
    {
        return self::CONTROLLER_NAMESPACE;
    }

}
