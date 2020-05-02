<?php
/**
 * User: Louis
 * Time: 2016/11/07 23:49
 * Update: 2018-05-21 12:22
 */

namespace ArrowWorker;

//ArrowWorker framework folder
defined('ArrowWorker') or define('ArrowWorker', __DIR__);

//application folder
defined('APP_DIR') or define('APP_DIR', 'App');

//application path
defined('APP_PATH') or define('APP_PATH', dirname(ArrowWorker) . '/' . APP_DIR);

//folder name for application controller
defined('APP_CONTROLLER_DIR') or define('APP_CONTROLLER_DIR', 'Controller');

//folder name for application model
defined('APP_MODEL_DIR') or define('APP_MODEL_DIR', 'Model');

//folder name for application class
defined('APP_CLASS_DIR') or define('APP_CLASS_DIR', 'Classes');

//folder name for application Runtime
defined('APP_RUNTIME_DIR') or define('APP_RUNTIME_DIR', 'Runtime');

//folder name for application service
defined('APP_SERVICE_DIR') or define('APP_SERVICE_DIR', 'Service');

//folder name for application Config
defined('APP_CONFIG_DIR') or define('APP_CONFIG_DIR', 'Config');

//folder name for application language
defined('APP_LANG_DIR') or define('APP_LANG_DIR', 'Lang');

//folder name for application view-tpl
defined('APP_TPL_DIR') or define('APP_TPL_DIR', 'Tpl');

//file name for default configuration
defined('APP_CONFIG_FILE') or define('APP_CONFIG_FILE', 'App');


/**
 * Class ArrowWorker
 * @package ArrowWorker
 */
class ArrowWorker
{
    /**
     * class extension
     */
    const EXT = '.php';

    /**
     * @var $container Container
     */
    private $container;

    /**
     * ArrowWorker constructor.
     */
    private function __construct()
    {
        $this->setAutoLoad();
        $this->initContainer();
        $this->container->Get(App::class, [$this->container])->Run();
    }

    private function initContainer()
    {
        $this->container = $container = new Container();
    }

    private function setAutoLoad()
    {
        spl_autoload_register([
            $this,
            'LoadClass',
        ]);
    }


    /**
     * Start : frame start method
     */
    public static function Start()
    {
        new self;
    }


    /**
     * LoadClass : auto-load class method
     * @param string $class
     * @author Louis
     */
    public function LoadClass(string $class)
    {
        $frameClass = $this->frameClassPath($class);
        if (file_exists($frameClass)) {
            $class = $frameClass;
            goto LOAD;
        }

        $class = $this->appClassPath($class);
        if (!file_exists($class)) {
            Log::Dump("{$class} not found ", Log::TYPE_NOTICE, 'AutoLoad');
            return;
        }
        LOAD:
        require $class;
    }


    /**
     * @param string $class
     * @return string
     */
    private function frameClassPath(string $class)
    {
        return ArrowWorker .
            str_replace([
                'ArrowWorker\\',
                '\\',
            ], [
                '/',
                '/',
            ], $class) .
            self::EXT;

    }

    private function appClassPath(string $class)
    {
        return dirname(ArrowWorker) . DIRECTORY_SEPARATOR . str_replace('\\', "/", $class) . self::EXT;

    }

}