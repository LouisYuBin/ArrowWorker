<?php
/**
 * User: Louis
 * Time: 2016/11/07 23:49
 * Update: 2018-05-21 12:22
 */

namespace ArrowWorker;
use ArrowWorker\App;
use ArrowWorker\Config;
use ArrowWorker\Exception;

//ArrowWorker framework folder
defined('ArrowWorker') or define('ArrowWorker', __DIR__);

//application folder
defined('APP_FOLDER') or define('APP_FOLDER','App');

//application path
defined('APP_PATH') or define('APP_PATH',dirname(__DIR__).'/'.APP_FOLDER);

//application type（cli:command line application, swWeb: swoole http application, web: nginx+fpm application）
defined('APP_TYPE') or define('APP_TYPE','web');

//application development status(debug:in dev status, online:in released status)
defined('APP_STATUS') or define('APP_STATUS','debug');

//folder name for application controller
defined('APP_CONTROLLER_FOLDER') or define('APP_CONTROLLER_FOLDER','Controller');

//folder name for application model
defined('APP_MODEL_FOLDER') or define('APP_MODEL_FOLDER','Model');

//folder name for application class
defined('APP_CLASS_FOLDER') or define('APP_CLASS_FOLDER','Classes');

//folder name for application Runtime
defined('APP_RUNTIME_FOLDER') or define('APP_RUNTIME_FOLDER','Runtime');

//folder name for application service
defined('APP_SERVICE_FOLDER') or define('APP_SERVICE_FOLDER','Service');

//folder name for application Config
defined('APP_CONFIG_FOLDER') or define('APP_CONFIG_FOLDER','Config');

//folder name for application language
defined('APP_LANG_FOLDER') or define('APP_LANG_FOLDER','Lang');

//folder name for application view-tpl
defined('APP_TPL_FOLDER') or define('APP_TPL_FOLDER','Tpl');

//default controller Class name
defined('DEFAULT_CONTROLLER') or define('DEFAULT_CONTROLLER','Index');

//default controller method name in default controller class
defined('DEFAULT_METHOD') or define('DEFAULT_METHOD','index');

//file name for default configuration
defined('APP_CONFIG_FILE') or define('APP_CONFIG_FILE','app');


/**
 * Class ArrowWorker
 * @package ArrowWorker
 */
class ArrowWorker
{
    /**
     * frame class extension
     */
    const classExt = '.class.php';

    /**
     * @var frame instance
     */
    private static $Arrow = null;

    /**
     * ArrowWorker constructor.
     */
    private function __construct()
    {
        spl_autoload_register(['self','loadClass']);
    }


    /**
     * Start : frame start method
     * @author Louis
     */
    static function Start(){
        if ( is_null(static::$Arrow) )
        {
            static::$Arrow = new self;
            Exception::Init();
        }
        App::RunApp();
    }


    /**
     * loadClass : auto-load class method
     * @author Louis
     * @param string $class
     */
    static function loadClass(string $class)
    {
        $ArrowClass = static::classMap();
        if( isset($ArrowClass[$class]) )
        {
            //frame class
            $class = $ArrowClass[$class];
        }
        else
        {
            $class = APP_PATH.DIRECTORY_SEPARATOR.str_replace(['\\',explode('\\', $class)[0]],"/",$class).static::classExt;
            if( !file_exists($class) )
            {
                throw new \Exception("Auto load class error : ".$class." does not exists.");
            }
        }
        require $class;
    }


    /**
     * classMap frame class alias
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
			'ArrowWorker\Session'    => ArrowWorker . '/Session'  . self::classExt,
            'ArrowWorker\Cookie'     => ArrowWorker . '/Cookie'   . self::classExt,
            'ArrowWorker\Response'   => ArrowWorker . '/Response' . self::classExt,
            'ArrowWorker\Request'    => ArrowWorker . '/Request'  . self::classExt,
            'ArrowWorker\Console'    => ArrowWorker . '/Console'  . self::classExt,
            'ArrowWorker\Swoole'     => ArrowWorker . '/Swoole'  . self::classExt,
            'ArrowWorker\Upload'     => ArrowWorker . '/Upload'  . self::classExt,
            'ArrowWorker\Lib\Crypto\CryptoArrow'     => ArrowWorker  . '/Lib/Crypto/CryptoArrow' . self::classExt,
            'ArrowWorker\Lib\Validation\ValidateImg' => ArrowWorker  . '/Lib/Validation/ValidateImg' . self::classExt,

            'ArrowWorker\Driver\Db'      => ArrowWorker . '/Driver/Db' .      self::classExt,
            'ArrowWorker\Driver\View'    => ArrowWorker . '/Driver/View' .    self::classExt,
            'ArrowWorker\Driver\Cache'   => ArrowWorker . '/Driver/Cache' .   self::classExt,
            'ArrowWorker\Driver\Daemon'  => ArrowWorker . '/Driver/Daemon' .  self::classExt,
            'ArrowWorker\Driver\Channel' => ArrowWorker . '/Driver/Channel'.  self::classExt,
            'ArrowWorker\Driver\Session' => ArrowWorker . '/Driver/Session'.  self::classExt,

            'ArrowWorker\Driver\Db\Mysqli'          => ArrowWorker . '/Driver/Db/Mysqli' .          self::classExt,
            'ArrowWorker\Driver\Db\SqlBuilder'      => ArrowWorker . '/Driver/Db/SqlBuilder' .      self::classExt,
            'ArrowWorker\Driver\Cache\Redis'        => ArrowWorker . '/Driver/Cache/Redis' .        self::classExt,
            'ArrowWorker\Driver\View\Smarty'        => ArrowWorker . '/Driver/View/Smarty' .        self::classExt,
            'ArrowWorker\Driver\Daemon\ArrowDaemon' => ArrowWorker . '/Driver/Daemon/ArrowDaemon' . self::classExt,
            'ArrowWorker\Driver\Daemon\ArrowThread' => ArrowWorker . '/Driver/Daemon/ArrowThread' . self::classExt,
            'ArrowWorker\Driver\Channel\Pipe'       => ArrowWorker . '/Driver/Channel/Pipe' .       self::classExt,
            'ArrowWorker\Driver\Channel\Queue'      => ArrowWorker . '/Driver/Channel/Queue' .      self::classExt,
			'ArrowWorker\Driver\Session\RedisSession' => ArrowWorker . '/Driver/Session/RedisSession' . self::classExt,
            'ArrowWorker\Driver\Session\MemcachedSession' => ArrowWorker . '/Driver/Session/MemcachedSession' . self::classExt,
        ];
    }

}