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
//应用目录名称
defined('APP_FOLDER') or define('APP_FOLDER','App');
//应用默认应用路径
defined('APP_PATH') or define('APP_PATH',dirname(__DIR__).'/'.APP_FOLDER);
//应用类型（命令行模式 or web应用）
defined('APP_TYPE') or define('APP_TYPE','web');
//状态：debug（开发） or online（上线）
defined('APP_STATUS') or define('APP_STATUS','debug');
//应用控制器目录名
defined('APP_CONTROLLER_FOLDER') or define('APP_CONTROLLER_FOLDER','Controller');
//应用模型目录名
defined('APP_MODEL_FOLDER') or define('APP_MODEL_FOLDER','Model');
//应用类目录名
defined('APP_CLASS_FOLDER') or define('APP_CLASS_FOLDER','Class');
//应用业务目录名
defined('APP_SERVICE_FOLDER') or define('APP_SERVICE_FOLDER','Service');
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
defined('APP_CONFIG_FILE') or define('APP_CONFIG_FILE','app');
//默认应用controller、class、model映射文件
defined('APP_ALIAS') or define('APP_ALIAS','cam');



class ArrowWorker
{
    const classExt = '.class.php';
    private static $Arrow;
    private static $app;


    private function __construct()
    {
        //class auto-load
        spl_autoload_register(['self','loadClass']);
    }

    static function exceptionHandle()
    {
        set_error_handler([self::$Arrow,'error']);
        set_exception_handler([self::$Arrow,'exception']);
    }

    //启动框架
    static function start(){
        if (!self::$Arrow)
        {
            self::$Arrow = new self;
        }
        self::exceptionHandle();
        self::$app = app::initApp();
        self::$app -> runApp();
    }

    //错误处理
    static function error()
    {
        if(APP_TYPE=='web' && APP_STATUS=='debug')
        {
            ob_clean();
            exit( json_encode( debug_backtrace() ) );
        }
        else if(APP_TYPE=='web' && APP_STATUS!='debug')
        {
            exit( json_encode( ['code' => 500, 'msg' => 'something is wrong with the server...'] ) );
        }
        else if(APP_TYPE=='cli')
        {
            var_dump(debug_backtrace());
        }
    }

    //异常处理
    static function exception($msg = null)
    {
        exit(json_encode( (array)$msg) );
    }

    //加载类
    static function loadClass($class)
    {
        $ArrowClass = self::classMap();
        if(isset($ArrowClass[$class]))
        {
            //系统类映射
            $class = $ArrowClass[$class];
        }
        else
        {
           //用户类映射
            $appClass  = config::Load(config::$AppFileMap);
           if(isset($appClass[$class]))
           {
               $class = APP_PATH.DIRECTORY_SEPARATOR.$appClass[$class];
           }
        }
        require $class;
    }

    //框架命名空间和文件路径映射
    static function classMap()
    {
        return [
            'ArrowWorker\Driver\Cache'  => ArrowWorker . '/Driver/Cache' .  self::classExt,
            'ArrowWorker\Driver\Db'     => ArrowWorker . '/Driver/Db' .     self::classExt,
            'ArrowWorker\Driver\Daemon' => ArrowWorker . '/Driver/Daemon' . self::classExt,
            'ArrowWorker\Driver\View'   => ArrowWorker . '/Driver/View'.self::classExt,
            'ArrowWorker\Driver\Cache\Redis' => ArrowWorker . '/Driver/Cache/Redis' . self::classExt,
            'ArrowWorker\Driver\Db\Mysqli'   => ArrowWorker . '/Driver/Db/Mysqli' .   self::classExt,
            'ArrowWorker\Driver\Daemon\ArrowDaemon' => ArrowWorker . '/Driver/Daemon/ArrowDaemon' . self::classExt,
            'ArrowWorker\Driver\Daemon\ArrowThread' => ArrowWorker . '/Driver/Daemon/ArrowThread' . self::classExt,
            'ArrowWorker\Driver\View\Smarty' => ArrowWorker . '/Driver/View/Smarty' . self::classExt,
            'ArrowWorker\Controller' => ArrowWorker . '/Controller' . self::classExt,
            'ArrowWorker\Factory'    => ArrowWorker . '/Factory' .    self::classExt,
            'ArrowWorker\App'        => ArrowWorker . '/App' .        self::classExt,
            'ArrowWorker\Model'      => ArrowWorker . '/Model' .      self::classExt,
            'ArrowWorker\Config'     => ArrowWorker . '/Config' .     self::classExt,
            'ArrowWorker\Loader'     => ArrowWorker . '/Loader' .     self::classExt,
            'ArrowWorker\Exception'  => ArrowWorker . '/Exception' .  self::classExt,
        ];
    }

}





