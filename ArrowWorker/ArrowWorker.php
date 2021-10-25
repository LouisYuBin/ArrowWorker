<?php
/**
 * User: Louis
 * Time: 2016/11/07 23:49
 * Update: 2018-05-21 12:22
 */

namespace ArrowWorker;

use ArrowWorker\Log\Log;

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
    private Container $container;

    /**
     * ArrowWorker constructor.
     */
    private function __construct()
    {
        $this->initAutoLoad();
        $this->initContainer();
        $this->container->get(Console::class, [$this->container])->run();
    }

    /**
     * 初始化容器
     */
    private function initContainer(): void
    {
        $this->container = new Container();
    }

    /**
     * 初始化自动加载
     */
    private function initAutoLoad(): void
    {
        spl_autoload_register([
            $this,
            'loadClass',
        ]);
    }


    /**
     * Start : frame start method
     */
    public static function start(): self
    {
        return new self;
    }


    /**
     * loadClass : auto-load class method
     * @param string $class
     * @author Louis
     * @return void
     */
    public function loadClass(string $class):void
    {
        $arrowClass = $this->getArrowClassPath($class);
        if (file_exists($arrowClass)) {
            $class = $arrowClass;
            goto LOAD_CLASS;
        }

        $class = $this->getAppClassPath($class);
        if (!file_exists($class)) {
            Log::Dump("{$class} not found ", Log::TYPE_NOTICE, 'AutoLoad');
            return;
        }
        LOAD_CLASS:
        require_once $class;
    }


    /**
     * @param string $class
     * @return string
     */
    private function getArrowClassPath(string $class): string
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

    /**
     * @param string $class
     * @return string
     */
    private function getAppClassPath(string $class): string
    {
        return dirname(ArrowWorker) . DIRECTORY_SEPARATOR . str_replace('\\', "/", $class) . self::EXT;

    }

}