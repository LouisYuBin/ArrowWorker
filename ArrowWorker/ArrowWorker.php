<?php
/**
 * User: Louis
 * Date: 2016/11/07
 * Time: 23:49
 */

namespace ArrowWorker;
use ArrowWorker\App as app;
use ArrowWorker\Config as config;
//框架目录
defined('ArrowWorker') or define('ArrowWorker', __DIR__);
//用户默认应用目录
defined('APP_PATH') or define('APP_PATH',dirname(__DIR__).'/App');
//应用类型（命令行模式 or web应用）
defined('APP_TYPE') or define('APP_TYPE','web');
//应用目录名称
defined('APP_FOLDER') or define('APP_FOLDER','App');
//应用控制器目录名
defined('APP_CONTROLLER_FOLDER') or define('APP_CONTROLLER_FOLDER','Controller');
//应用模型目录名
defined('APP_Model_FOLDER') or define('APP_Model_FOLDER','Model');
//应用类目录名
defined('APP_Class_FOLDER') or define('APP_Class_FOLDER','Classes');
//应用配置文件夹
defined('APP_CONFIG_FOLDER') or define('APP_CONFIG_FOLDER','Config');
//应用语言文件夹
defined('APP_LANG_FOLDER') or define('APP_LANG_FOLDER','Lang');
//应用配置文件夹
defined('APP_TPL_FOLDER') or define('APP_TPL_FOLDER','Tpl');
//默认控制器
defined('DEFAULT_CONTROLLER') or define('DEFAULT_CONTROLLER','Index');
//默认控制器方法
defined('DEFAULT_METHOD') or define('DEFAULT_METHOD','index');
//默认应用配置文件
defined('APP_CONFIG_FILE') or define('APP_CONFIG_FILE','common');
//默认应用controller、class、model映射文件
defined('APP_ALIAS') or define('APP_ALIAS','cam');



class ArrowWorker
{
    const classExt = '.class.php';
    private static $instance;
    private static $app;
    private static $appConfig = [
        'app'        => 'App',
        'config'     => 'Config',
        'controller' => 'Controller',
        'model'      => 'Model',
        'class'      => 'Class',
        'alias'      => 'alias',
        'userCam'    => 'cam.php'
        ];

    private function __construct()
    {
        spl_autoload_register(['self','loadClass']);
    }

    //启动框架
    static function start(){
        if (!self::$instance)
        {
            self::$instance = new self;
        }
        self::$app = app::initApp(self::$appConfig);
        self::$app -> runApp();
    }

    //加载类
    static function loadClass($class)
    {
        $frameClass = self::classMap();
        if(isset($frameClass[$class]))
        {
            //系统类映射
            $class = $frameClass[$class];
        }
        else
        {
           //用户类映射
           $appAlias  = config::get(self::$appConfig['alias']);
           if(isset($appAlias[$class]))
           {
               $class = APP_PATH.DIRECTORY_SEPARATOR.$appAlias[$class];
           }
           else
           {
               exit('Class do not exists');
           }
        }
        echo $class.PHP_EOL;
        require $class;
    }

    //框架命名空间和文件路径映射
    static function classMap()
    {
        $classExt = self::classExt;
        return [
            'ArrowWorker\Driver\Cache'  => ArrowWorker . '/Driver/' . 'Cache' . $classExt,
            'ArrowWorker\Driver\Db'     => ArrowWorker . '/Driver/' . 'Db' . $classExt,
            'ArrowWorker\Driver\Daemon' => ArrowWorker . '/Driver/' . 'Daemon' . $classExt,
            'ArrowWorker\Driver\View' => ArrowWorker . '/Driver/View'.$classExt,
            'ArrowWorker\Driver\Cache\Redis' => ArrowWorker . '/Driver/Cache/Redis' . $classExt,
            'ArrowWorker\Driver\Db\Mysqli'   => ArrowWorker . '/Driver/Db/Mysqli' . $classExt,
            'ArrowWorker\Driver\Daemon\ArrowDaemon' => ArrowWorker . '/Driver/Daemon/ArrowDaemon' . $classExt,
            'ArrowWorker\Driver\View\Smarty' => ArrowWorker . '/Driver/View/Smarty' . $classExt,
            'ArrowWorker\Driver\View\Smarty' => ArrowWorker . '/Driver/View/Smarty' . $classExt,
            'ArrowWorker\Controller'   => ArrowWorker . '/Controller'.$classExt,
            'ArrowWorker\Factory' => ArrowWorker . '/Factory'.$classExt,
            'ArrowWorker\App'   => ArrowWorker . '/App'.$classExt,
            'ArrowWorker\Model' => ArrowWorker . '/Model'.$classExt,
            'ArrowWorker\Config' => ArrowWorker . '/Config'.$classExt
        ];
    }

}





