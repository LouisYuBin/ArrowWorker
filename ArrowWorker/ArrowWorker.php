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

//file name for default configuration
defined('APP_CONFIG_FILE') or define('APP_CONFIG_FILE','App');

defined('DEBUG') or define('DEBUG', true);
defined('ENV') or define('ENV', 'Dev');


/**
 * Class ArrowWorker
 * @package ArrowWorker
 */
class ArrowWorker
{
    /**
     * class extension
     */
    const CLASS_EXT = '.class.php';

    const INTERFACE_EXT = '.interface.php';

    /**
     * @var $Arrow ArrowWorker
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
        App::Run();
    }


    /**
     * _loadClass : auto-load class method
     * @author Louis
     * @param string $class
     */
    static function _loadClass(string $class)
    {
        $ArrowClass = self::_classMap();
        if( isset($ArrowClass[$class]) )
        {
            //frame class
            $class = $ArrowClass[$class];
        }
        else
        {
            $class = APP_PATH.DIRECTORY_SEPARATOR.str_replace(['\\',explode('\\', $class)[0]],"/",$class).static::CLASS_EXT;
            if( !file_exists($class) )
            {
                $msg = "Auto load class error : ".$class." does not exists.";
                Log::Error($msg);
                return ;
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
            'ArrowWorker\App'        => ArrowWorker . '/App'        . self::CLASS_EXT,
            'ArrowWorker\Model'      => ArrowWorker . '/Model'      . self::CLASS_EXT,
            'ArrowWorker\Loader'     => ArrowWorker . '/Loader'     . self::CLASS_EXT,
            'ArrowWorker\Config'     => ArrowWorker . '/Config'     . self::CLASS_EXT,
            'ArrowWorker\Exception'  => ArrowWorker . '/Exception'  . self::CLASS_EXT,

            'ArrowWorker\Pool'       => ArrowWorker . '/Pool'      . self::INTERFACE_EXT,
            'ArrowWorker\Console'    => ArrowWorker . '/Console'   . self::CLASS_EXT,
            'ArrowWorker\Daemon'     => ArrowWorker . '/Daemon'    . self::CLASS_EXT,

            'ArrowWorker\Log'        => ArrowWorker . '/Log'       . self::CLASS_EXT,
            'ArrowWorker\Worker'     => ArrowWorker . '/Worker'    . self::CLASS_EXT,
            'ArrowWorker\Db'         => ArrowWorker . '/Db'        . self::CLASS_EXT,
            'ArrowWorker\Chan'       => ArrowWorker . '/Chan'      . self::CLASS_EXT,
            'ArrowWorker\Memory'     => ArrowWorker . '/Memory'    . self::CLASS_EXT,
            'ArrowWorker\Component'  => ArrowWorker . '/Component' . self::CLASS_EXT,
            'ArrowWorker\Cache'      => ArrowWorker . '/Cache'     . self::CLASS_EXT,

            'ArrowWorker\Web\Session'    => ArrowWorker . '/Web/Session'  . self::CLASS_EXT,
            'ArrowWorker\Web\Cookie'     => ArrowWorker . '/Web/Cookie'   . self::CLASS_EXT,
            'ArrowWorker\Web\Response'   => ArrowWorker . '/Web/Response' . self::CLASS_EXT,
            'ArrowWorker\Web\Request'    => ArrowWorker . '/Web/Request'  . self::CLASS_EXT,
            'ArrowWorker\Web\Router'     => ArrowWorker . '/Web/Router'   . self::CLASS_EXT,
            'ArrowWorker\Web\Upload'     => ArrowWorker . '/Web/Upload'   . self::CLASS_EXT,

            'ArrowWorker\Lib\Bytes'                   => ArrowWorker  . '/Lib/Crypto/Bytes'            . self::CLASS_EXT,
            'ArrowWorker\Lib\Crypto\CryptoArrow'      => ArrowWorker  . '/Lib/Crypto/CryptoArrow'      . self::CLASS_EXT,
            'ArrowWorker\Lib\Validation\ValidateImg'  => ArrowWorker  . '/Lib/Validation/ValidateImg'  . self::CLASS_EXT,
            'ArrowWorker\Lib\Image\Gd'                => ArrowWorker  . '/Lib/Image/Gd'                . self::CLASS_EXT,
            'ArrowWorker\Lib\Image\ImageMagick'       => ArrowWorker  . '/Lib/Image/ImageMagick'       . self::CLASS_EXT,
            'ArrowWorker\Lib\Image\Image'             => ArrowWorker  . '/Lib/Image/Image'             . self::CLASS_EXT,
            'ArrowWorker\Lib\Image\ImageInterface'    => ArrowWorker  . '/Lib/Image/ImageInterface'    . self::INTERFACE_EXT,
            'ArrowWorker\Lib\Image\Gif\GifHelper'     => ArrowWorker  . '/Lib/Image/Gif/GifHelper'     . self::CLASS_EXT,
            'ArrowWorker\Lib\Image\Gif\GifByteStream' => ArrowWorker  . '/Lib/Image/Gif/GifByteStream' . self::CLASS_EXT,
            'ArrowWorker\Lib\System\LoadAverage'      => ArrowWorker  . '/Lib/System/LoadAverage'      . self::CLASS_EXT,

            'ArrowWorker\Lib\Coroutine' => ArrowWorker . '/Lib/Coroutine' . self::CLASS_EXT,
            'ArrowWorker\Lib\Process'   => ArrowWorker . '/Lib/Process'   . self::CLASS_EXT,

            'ArrowWorker\Server\Http' => ArrowWorker . '/Server/Http' . self::CLASS_EXT,
            'ArrowWorker\Server\Ws'   => ArrowWorker . '/Server/Ws'   . self::CLASS_EXT,
            'ArrowWorker\Server\Tcp'  => ArrowWorker . '/Server/Tcp' . self::CLASS_EXT,
            'ArrowWorker\Server\Udp'  => ArrowWorker . '/Server/Udp' . self::CLASS_EXT,
            'ArrowWorker\Server\GRpc' => ArrowWorker . '/Server/GRpc' . self::CLASS_EXT,

            'ArrowWorker\Client\Ws\Pool'   => ArrowWorker . '/Client/Ws/Pool'       . self::CLASS_EXT,
            'ArrowWorker\Client\Ws\Client' => ArrowWorker . '/Client/Ws/Client' . self::CLASS_EXT,

            'ArrowWorker\Client\Http\Pool' => ArrowWorker . '/Client/Http/Pool' . self::CLASS_EXT,
            'ArrowWorker\Client\Http\Http' => ArrowWorker . '/Client/Http/Http' . self::CLASS_EXT,
            'ArrowWorker\Client\Http\Http2' => ArrowWorker . '/Client/Http/Http2' . self::CLASS_EXT,

            'ArrowWorker\Client\Tcp\Pool'        => ArrowWorker . '/Client/Tcp/Pool' . self::CLASS_EXT,
            'ArrowWorker\Client\Tcp\Client' => ArrowWorker . '/Client/Tcp/Client' . self::CLASS_EXT,

            'ArrowWorker\Client\GRpc\Pool'   => ArrowWorker . '/Client/GRpc/Pool' . self::CLASS_EXT,
            'ArrowWorker\Client\GRpc\Client' => ArrowWorker . '/Client/GRpc/Client' . self::CLASS_EXT,

            'ArrowWorker\Lib\Xml\Writer'    => ArrowWorker . '/Lib/Xml/Writer'    . self::CLASS_EXT,
            'ArrowWorker\Lib\Xml\Reader'    => ArrowWorker . '/Lib/Xml/Reader'    . self::CLASS_EXT,
            'ArrowWorker\Lib\Xml\Converter' => ArrowWorker . '/Lib/Xml/Converter' . self::CLASS_EXT,

            'ArrowWorker\Driver\View'    => ArrowWorker . '/Driver/View'    . self::CLASS_EXT,
            'ArrowWorker\Driver\Worker'  => ArrowWorker . '/Driver/Worker'  . self::CLASS_EXT,
            'ArrowWorker\Driver\Session' => ArrowWorker . '/Driver/Session' . self::CLASS_EXT,

            'ArrowWorker\Driver\Db\Db'         => ArrowWorker . '/Driver/Db/Db'     . self::INTERFACE_EXT,
            'ArrowWorker\Driver\Db\Mysqli'     => ArrowWorker . '/Driver/Db/Mysqli' . self::CLASS_EXT,
            'ArrowWorker\Driver\Db\Pdo'        => ArrowWorker . '/Driver/Db/Pdo'    . self::CLASS_EXT,
            'ArrowWorker\Driver\Db\Pool'       => ArrowWorker . '/Driver/Db/Pool'   . self::CLASS_EXT,
            'ArrowWorker\Driver\Db\SqlBuilder' => ArrowWorker . '/Driver/Db/SqlBuilder' . self::CLASS_EXT,

            'ArrowWorker\Driver\Cache\Cache' => ArrowWorker . '/Driver/Cache/Cache'   . self::INTERFACE_EXT,
            'ArrowWorker\Driver\Cache\Redis' => ArrowWorker . '/Driver/Cache/Redis'   . self::CLASS_EXT,
            'ArrowWorker\Driver\Cache\Pool'  => ArrowWorker . '/Driver/Cache/Pool'    . self::CLASS_EXT,

            'ArrowWorker\Driver\View\Smarty'        => ArrowWorker . '/Driver/View/Smarty'        . self::CLASS_EXT,
            'ArrowWorker\Driver\Worker\ArrowDaemon' => ArrowWorker . '/Driver/Worker/ArrowDaemon' . self::CLASS_EXT,
            'ArrowWorker\Driver\Channel\Queue'      => ArrowWorker . '/Driver/Channel/Queue'      .  self::CLASS_EXT,
			'ArrowWorker\Driver\Session\RedisSession' => ArrowWorker . '/Driver/Session/RedisSession' . self::CLASS_EXT,
            'ArrowWorker\Driver\Session\MemcachedSession' => ArrowWorker . '/Driver/Session/MemcachedSession' . self::CLASS_EXT,

            'ArrowWorker\Driver\Memory\SwTable' => ArrowWorker . '/Driver/Memory/SwTable' . self::CLASS_EXT,
        ];
    }

}