<?php
/**
 * User: Louis
 * Date: 2016/11/07
 * Time: 23:49
 */

namespace ArrowWorker;
use ArrowWorker\App;
use ArrowWorker\Config;
use ArrowWorker\Exception;
//框架目录
defined('ArrowWorker') or define('ArrowWorker', __DIR__);
//应用目录名称
defined('APP_FOLDER') or define('APP_FOLDER','App');
//应用默认应用路径
defined('APP_PATH') or define('APP_PATH',dirname(__DIR__).'/'.APP_FOLDER);
//应用类型（cli:命令行模式 swoole: 用swoole作为引擎的web的web，web：使用php-fpm做为引擎的web）
defined('APP_TYPE') or define('APP_TYPE','web');
//状态：debug（开发） or online（上线）
defined('APP_STATUS') or define('APP_STATUS','debug');
//应用控制器目录名
defined('APP_CONTROLLER_FOLDER') or define('APP_CONTROLLER_FOLDER','Controller');
//应用模型目录名
defined('APP_MODEL_FOLDER') or define('APP_MODEL_FOLDER','Model');
//应用类目录名
defined('APP_CLASS_FOLDER') or define('APP_CLASS_FOLDER','Classes');
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


/**
 * Class ArrowWorker 框架入口类
 * @package ArrowWorker
 */
class ArrowWorker
{
    /**
     * 框架类文件后缀
     */
    const classExt = '.class.php';

    /**
     * @var 入口类实例对象
     */
    private static $Arrow;

    /**
     * ArrowWorker constructor.
     */
    private function __construct()
    {
        spl_autoload_register(['self','loadClass']);
    }


    /**
     * Start 启动框架
     * @author Louis
     */
    static function Start(){
        if (!static::$Arrow)
        {
            static::$Arrow = new self;
            //初始化异常和错误处理
            Exception::Init();
        }
        App::InitApp() -> RunApp();
    }


    /**
     * loadClass 加载框架/用户类
     * @author Louis
     * @param string $class
     */
    static function loadClass(string $class)
    {
        $ArrowClass = static::classMap();
        if(isset($ArrowClass[$class]))
        {
            //系统类映射
            $class = $ArrowClass[$class];
        }
        else
        {
           //用户类映射
            $appClass  = Config::Load(Config::$AppFileMap);
           if( !isset($appClass[$class]) )
           {
               throw new \Exception("Auto load class error : ".$class." is not added to user class map.");
           }
           $class = APP_PATH.DIRECTORY_SEPARATOR.$appClass[$class];
            if( !file_exists($class) )
            {
                throw new \Exception("Auto load class error : ".$class." does not exists.");
            }
        }
        require $class;
    }


    /**
     * classMap 框架命名空间和文件路径映射
     * @author Louis
     * @return array
     */
    static function classMap()
    {
        return [
            'ArrowWorker\App'        => ArrowWorker . '/App' .        self::classExt,
            'ArrowWorker\Model'      => ArrowWorker . '/Model' .      self::classExt,
            'ArrowWorker\Loader'     => ArrowWorker . '/Loader' .     self::classExt,
            'ArrowWorker\Router'     => ArrowWorker . '/Router' .     self::classExt,
            'ArrowWorker\Config'     => ArrowWorker . '/Config' .     self::classExt,
            'ArrowWorker\Driver'     => ArrowWorker . '/Driver' .     self::classExt,
            'ArrowWorker\Exception'  => ArrowWorker . '/Exception' .  self::classExt,
            'ArrowWorker\Controller' => ArrowWorker . '/Controller' . self::classExt,
			'ArrowWorker\Session'    => ArrowWorker . '/Session' . self::classExt,

            'ArrowWorker\Driver\Db'      => ArrowWorker . '/Driver/Db' .      self::classExt,
            'ArrowWorker\Driver\View'    => ArrowWorker . '/Driver/View' .    self::classExt,
            'ArrowWorker\Driver\Cache'   => ArrowWorker . '/Driver/Cache' .   self::classExt,
            'ArrowWorker\Driver\Daemon'  => ArrowWorker . '/Driver/Daemon' .  self::classExt,
            'ArrowWorker\Driver\Channel' => ArrowWorker . '/Driver/Channel'. self::classExt,

            'ArrowWorker\Driver\Db\Mysqli'          => ArrowWorker . '/Driver/Db/Mysqli' .          self::classExt,
            'ArrowWorker\Driver\Db\SqlBuilder'      => ArrowWorker . '/Driver/Db/SqlBuilder' .      self::classExt,
            'ArrowWorker\Driver\Cache\Redis'        => ArrowWorker . '/Driver/Cache/Redis' .        self::classExt,
            'ArrowWorker\Driver\View\Smarty'        => ArrowWorker . '/Driver/View/Smarty' .        self::classExt,
            'ArrowWorker\Driver\Daemon\ArrowDaemon' => ArrowWorker . '/Driver/Daemon/ArrowDaemon' . self::classExt,
            'ArrowWorker\Driver\Daemon\ArrowThread' => ArrowWorker . '/Driver/Daemon/ArrowThread' . self::classExt,
            'ArrowWorker\Driver\Channel\Pipe'       => ArrowWorker . '/Driver/Channel/Pipe' .       self::classExt,
            'ArrowWorker\Driver\Channel\Queue'      => ArrowWorker . '/Driver/Channel/Queue' .      self::classExt,
			'ArrowWorker\Driver\Session\RedisSession' => ArrowWorker . '/Driver/Session/RedisSession' . self::classExt,

        ];
    }

}





