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
defined('APP_DIR') or define('APP_DIR','App');

//application path
defined('APP_PATH') or define('APP_PATH',dirname(__DIR__).'/'.APP_DIR);

//application type（worker:command line application, swWeb: swoole http application, web: nginx+fpm application）
defined('APP_TYPE') or define('APP_TYPE','web');

//application development status(debug:in dev status, online:in released status)
defined('APP_STATUS') or define('APP_STATUS','debug');

//folder name for application controller
defined('APP_CONTROLLER_DIR') or define('APP_CONTROLLER_DIR','Controller');

//folder name for application model
defined('APP_MODEL_DIR') or define('APP_MODEL_DIR','Model');

//folder name for application class
defined('APP_CLASS_DIR') or define('APP_CLASS_DIR','Classes');

//folder name for application Runtime
defined('APP_RUNTIME_DIR') or define('APP_RUNTIME_DIR','Runtime');

//folder name for application service
defined('APP_SERVICE_DIR') or define('APP_SERVICE_DIR','Service');

//folder name for application Config
defined('APP_CONFIG_DIR') or define('APP_CONFIG_DIR','Config');

//folder name for application language
defined('APP_LANG_DIR') or define('APP_LANG_DIR','Lang');

//folder name for application view-tpl
defined('APP_TPL_DIR') or define('APP_TPL_DIR','Tpl');

//default controller Class name
defined('DEFAULT_CONTROLLER') or define('DEFAULT_CONTROLLER','Index');

//default controller method name in default controller class
defined('DEFAULT_METHOD') or define('DEFAULT_METHOD','index');

//file name for default configuration
defined('APP_CONFIG_FILE') or define('APP_CONFIG_FILE','App');


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
        spl_autoload_register(['self','_loadClass']);
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
     * _loadClass : auto-load class method
     * @author Louis
     * @param string $class
     */
    static function _loadClass(string $class)
    {
        $ArrowClass = static::_classMap();
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
                $msg = "Auto load class error : ".$class." does not exists.";
                Log::Error($msg);
                Log::DumpExit($msg);
            }
        }
        require $class;
    }


    /**
     * classMap frame class alias
     * @author Louis
     * @return array
     */
    static function _classMap()
    {
        return [
            'ArrowWorker\App'        => ArrowWorker . '/App' .        self::classExt,
            'ArrowWorker\Model'      => ArrowWorker . '/Model' .      self::classExt,
            'ArrowWorker\Loader'     => ArrowWorker . '/Loader' .     self::classExt,
            'ArrowWorker\Config'     => ArrowWorker . '/Config' .     self::classExt,
            'ArrowWorker\Driver'     => ArrowWorker . '/Driver' .     self::classExt,
            'ArrowWorker\Exception'  => ArrowWorker . '/Exception' .  self::classExt,
            'ArrowWorker\Controller' => ArrowWorker . '/Controller' . self::classExt,

            'ArrowWorker\Console'    => ArrowWorker . '/Console'  . self::classExt,
            'ArrowWorker\Daemon'     => ArrowWorker . '/Daemon'  . self::classExt,
            'ArrowWorker\Log'        => ArrowWorker . '/Log'  . self::classExt,
            'ArrowWorker\Swoole'     => ArrowWorker . '/Swoole'  . self::classExt,
            'ArrowWorker\Worker'     => ArrowWorker . '/Worker'  . self::classExt,
            'ArrowWorker\Db'         => ArrowWorker . '/Db'      . self::classExt,
            'ArrowWorker\Chan'       => ArrowWorker . '/Chan'    . self::classExt,

            'ArrowWorker\Web\Session'    => ArrowWorker . '/Web/Session'  . self::classExt,
            'ArrowWorker\Web\Cookie'     => ArrowWorker . '/Web/Cookie'   . self::classExt,
            'ArrowWorker\Web\Response'   => ArrowWorker . '/Web/Response' . self::classExt,
            'ArrowWorker\Web\Request'    => ArrowWorker . '/Web/Request'  . self::classExt,
            'ArrowWorker\Web\Router'     => ArrowWorker . '/Web/Router'   . self::classExt,
            'ArrowWorker\Web\Upload'     => ArrowWorker . '/Web/Upload'   . self::classExt,

            'ArrowWorker\Lib\Bytes'                  => ArrowWorker  . '/Lib/Crypto/Bytes' . self::classExt,
            'ArrowWorker\Lib\Crypto\CryptoArrow'     => ArrowWorker  . '/Lib/Crypto/CryptoArrow' . self::classExt,
            'ArrowWorker\Lib\Validation\ValidateImg' => ArrowWorker  . '/Lib/Validation/ValidateImg' . self::classExt,
            'ArrowWorker\Lib\Image\Gd'          => ArrowWorker  . '/Lib/Image/Gd' . self::classExt,
            'ArrowWorker\Lib\Image\ImageMagick' => ArrowWorker  . '/Lib/Image/ImageMagick' . self::classExt,
            'ArrowWorker\Lib\Image\Image'       => ArrowWorker  . '/Lib/Image/Image' . self::classExt,
            'ArrowWorker\Lib\Image\ImageInterface'    => ArrowWorker  . '/Lib/Image/ImageInterface' . self::classExt,
            'ArrowWorker\Lib\Image\Gif\GifHelper'     => ArrowWorker  . '/Lib/Image/Gif/GifHelper' . self::classExt,
            'ArrowWorker\Lib\Image\Gif\GifByteStream' => ArrowWorker  . '/Lib/Image/Gif/GifByteStream' . self::classExt,
            'ArrowWorker\Lib\System\LoadAverage'      => ArrowWorker  . '/Lib/System/LoadAverage' . self::classExt,
            'ArrowWorker\Lib\Client\WebSocket'     => ArrowWorker . '/Lib/Client/WebSocket'  . self::classExt,

            'ArrowWorker\Driver\Db'      => ArrowWorker . '/Driver/Db' .      self::classExt,
            'ArrowWorker\Driver\View'    => ArrowWorker . '/Driver/View' .    self::classExt,
            'ArrowWorker\Driver\Cache'   => ArrowWorker . '/Driver/Cache' .   self::classExt,
            'ArrowWorker\Driver\Worker'  => ArrowWorker . '/Driver/Worker' .  self::classExt,
            'ArrowWorker\Driver\Channel' => ArrowWorker . '/Driver/Channel'.  self::classExt,
            'ArrowWorker\Driver\Session' => ArrowWorker . '/Driver/Session'.  self::classExt,

            'ArrowWorker\Driver\Db\Mysqli'          => ArrowWorker . '/Driver/Db/Mysqli' .          self::classExt,
            'ArrowWorker\Driver\Db\SqlBuilder'      => ArrowWorker . '/Driver/Db/SqlBuilder' .      self::classExt,
            'ArrowWorker\Driver\Cache\Redis'        => ArrowWorker . '/Driver/Cache/Redis' .        self::classExt,
            'ArrowWorker\Driver\View\Smarty'        => ArrowWorker . '/Driver/View/Smarty' .        self::classExt,
            'ArrowWorker\Driver\Worker\ArrowDaemon' => ArrowWorker . '/Driver/Worker/ArrowDaemon' . self::classExt,
            'ArrowWorker\Driver\Channel\Pipe'       => ArrowWorker . '/Driver/Channel/Pipe' .       self::classExt,
            'ArrowWorker\Driver\Channel\Queue'      => ArrowWorker . '/Driver/Channel/Queue' .      self::classExt,
			'ArrowWorker\Driver\Session\RedisSession' => ArrowWorker . '/Driver/Session/RedisSession' . self::classExt,
            'ArrowWorker\Driver\Session\MemcachedSession' => ArrowWorker . '/Driver/Session/MemcachedSession' . self::classExt,
        ];
    }

}