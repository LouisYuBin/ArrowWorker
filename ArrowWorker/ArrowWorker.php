<?php
/**
 * User: Louis
 * Time: 2016/11/07 23:49
 * Update: 2018-05-21 12:22
 */

namespace ArrowWorker;

//ArrowWorker framework folder
defined( 'ArrowWorker' ) or define( 'ArrowWorker', __DIR__ );

//application folder
defined( 'APP_DIR' ) or define( 'APP_DIR', 'App' );

//application path
defined( 'APP_PATH' ) or define( 'APP_PATH', dirname( __DIR__ ) . '/' . APP_DIR );

//application type（worker:command line application, swWeb: swoole http application, web: nginx+fpm application）
defined( 'APP_TYPE' ) or define( 'APP_TYPE', 'web' );

//application development status(debug:in dev status, online:in released status)
defined( 'APP_STATUS' ) or define( 'APP_STATUS', 'debug' );

//folder name for application controller
defined( 'APP_CONTROLLER_DIR' ) or define( 'APP_CONTROLLER_DIR', 'Controller' );

//folder name for application model
defined( 'APP_MODEL_DIR' ) or define( 'APP_MODEL_DIR', 'Model' );

//folder name for application class
defined( 'APP_CLASS_DIR' ) or define( 'APP_CLASS_DIR', 'Classes' );

//folder name for application Runtime
defined( 'APP_RUNTIME_DIR' ) or define( 'APP_RUNTIME_DIR', 'Runtime' );

//folder name for application service
defined( 'APP_SERVICE_DIR' ) or define( 'APP_SERVICE_DIR', 'Service' );

//folder name for application Config
defined( 'APP_CONFIG_DIR' ) or define( 'APP_CONFIG_DIR', 'Config' );

//folder name for application language
defined( 'APP_LANG_DIR' ) or define( 'APP_LANG_DIR', 'Lang' );

//folder name for application view-tpl
defined( 'APP_TPL_DIR' ) or define( 'APP_TPL_DIR', 'Tpl' );

//file name for default configuration
defined( 'APP_CONFIG_FILE' ) or define( 'APP_CONFIG_FILE', 'App' );

defined( 'DEBUG' ) or define( 'DEBUG', true );


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
    private static $_arrow = null;

    /**
     * ArrowWorker constructor.
     */
    private function __construct()
    {
        spl_autoload_register( [
            $this,
            'LoadClass',
        ] );
    }


    /**
     * Start : frame start method
     * @author Louis
     */
    static function Start()
    {
        if ( self::$_arrow instanceof self )
        {
            return;
        }
        self::$_arrow = new self;
        App::Run();
    }


    /**
     * LoadClass : auto-load class method
     * @param string $class
     * @author Louis
     */
    public function LoadClass( string $class )
    {
        $fileAlias = $this->_GetAutoLoadAlias();
        if ( isset( $fileAlias[ $class ] ) )
        {
            $class = $fileAlias[ $class ];
        }
        else
        {
            $class = APP_PATH . '/' . str_replace( [ '\\', explode( '\\', $class )[ 0 ], ], "/", $class ) . static::CLASS_EXT;
            if ( !file_exists( $class ) )
            {
                Log::Error( "Auto load class error : " . $class . " does not exists." );
                return;
            }
        }
        require $class;
    }


    /**
     * _GetAutoLoadAlias frame class alias
     * @return array
     * @author Louis
     */
    private function _GetAutoLoadAlias()
    {
        return [
            'ArrowWorker\App'       => ArrowWorker . '/App' . self::CLASS_EXT,
            'ArrowWorker\Di'        => ArrowWorker . '/Di' . self::CLASS_EXT,
            'ArrowWorker\Config'    => ArrowWorker . '/Config' . self::CLASS_EXT,
            'ArrowWorker\Exception' => ArrowWorker . '/Exception' . self::CLASS_EXT,

            'ArrowWorker\Pool'    => ArrowWorker . '/Pool' . self::INTERFACE_EXT,
            'ArrowWorker\Console' => ArrowWorker . '/Console' . self::CLASS_EXT,
            'ArrowWorker\Daemon'  => ArrowWorker . '/Daemon' . self::CLASS_EXT,

            'ArrowWorker\Log'       => ArrowWorker . '/Log' . self::CLASS_EXT,
            'ArrowWorker\Worker'    => ArrowWorker . '/Worker' . self::CLASS_EXT,
            'ArrowWorker\Db'        => ArrowWorker . '/Db' . self::CLASS_EXT,
            'ArrowWorker\Chan'      => ArrowWorker . '/Chan' . self::CLASS_EXT,
            'ArrowWorker\Memory'    => ArrowWorker . '/Memory' . self::CLASS_EXT,
            'ArrowWorker\Component' => ArrowWorker . '/Component' . self::CLASS_EXT,
            'ArrowWorker\Cache'     => ArrowWorker . '/Cache' . self::CLASS_EXT,

            'ArrowWorker\Web\Session'  => ArrowWorker . '/Web/Session' . self::CLASS_EXT,
            'ArrowWorker\Web\Response' => ArrowWorker . '/Web/Response' . self::CLASS_EXT,
            'ArrowWorker\Web\Request'  => ArrowWorker . '/Web/Request' . self::CLASS_EXT,
            'ArrowWorker\Web\Router'   => ArrowWorker . '/Web/Router' . self::CLASS_EXT,
            'ArrowWorker\Web\Upload'   => ArrowWorker . '/Web/Upload' . self::CLASS_EXT,

            'ArrowWorker\Library\Bytes'                   => ArrowWorker . '/Library/Crypto/Bytes' . self::CLASS_EXT,
            'ArrowWorker\Library\Coroutine'               => ArrowWorker . '/Library/Coroutine' . self::CLASS_EXT,
            'ArrowWorker\Library\Channel'                 => ArrowWorker . '/Library/Channel' . self::CLASS_EXT,
            'ArrowWorker\Library\Process'                 => ArrowWorker . '/Library/Process' . self::CLASS_EXT,
            'ArrowWorker\Library\Crypto\CryptoArrow'      => ArrowWorker . '/Library/Crypto/CryptoArrow' . self::CLASS_EXT,
            'ArrowWorker\Library\Validation\ValidateImg'  => ArrowWorker . '/Library/Validation/ValidateImg' . self::CLASS_EXT,
            'ArrowWorker\Library\Image\Gd'                => ArrowWorker . '/Library/Image/Gd' . self::CLASS_EXT,
            'ArrowWorker\Library\Image\ImageMagick'       => ArrowWorker . '/Library/Image/ImageMagick' . self::CLASS_EXT,
            'ArrowWorker\Library\Image\Image'             => ArrowWorker . '/Library/Image/Image' . self::CLASS_EXT,
            'ArrowWorker\Library\Image\ImageInterface'    => ArrowWorker . '/Library/Image/ImageInterface' . self::INTERFACE_EXT,
            'ArrowWorker\Library\Image\Gif\GifHelper'     => ArrowWorker . '/Library/Image/Gif/GifHelper' . self::CLASS_EXT,
            'ArrowWorker\Library\Image\Gif\GifByteStream' => ArrowWorker . '/Library/Image/Gif/GifByteStream' . self::CLASS_EXT,
            'ArrowWorker\Library\System\LoadAverage'      => ArrowWorker . '/Library/System/LoadAverage' . self::CLASS_EXT,

            'ArrowWorker\Library\Xml\Writer'    => ArrowWorker . '/Library/Xml/Writer' . self::CLASS_EXT,
            'ArrowWorker\Library\Xml\Reader'    => ArrowWorker . '/Library/Xml/Reader' . self::CLASS_EXT,
            'ArrowWorker\Library\Xml\Converter' => ArrowWorker . '/Library/Xml/Converter' . self::CLASS_EXT,

            'ArrowWorker\Server\Server' => ArrowWorker . '/Server/Server' . self::CLASS_EXT,
            'ArrowWorker\Server\Http'   => ArrowWorker . '/Server/Http' . self::CLASS_EXT,
            'ArrowWorker\Server\Ws'     => ArrowWorker . '/Server/Ws' . self::CLASS_EXT,
            'ArrowWorker\Server\Tcp'    => ArrowWorker . '/Server/Tcp' . self::CLASS_EXT,
            'ArrowWorker\Server\Udp'    => ArrowWorker . '/Server/Udp' . self::CLASS_EXT,
            'ArrowWorker\Server\GRpc'   => ArrowWorker . '/Server/GRpc' . self::CLASS_EXT,

            'ArrowWorker\Client\Ws\Pool'   => ArrowWorker . '/Client/Ws/Pool' . self::CLASS_EXT,
            'ArrowWorker\Client\Ws\Client' => ArrowWorker . '/Client/Ws/Client' . self::CLASS_EXT,

            'ArrowWorker\Client\Http\Pool'  => ArrowWorker . '/Client/Http/Pool' . self::CLASS_EXT,
            'ArrowWorker\Client\Http\Http'  => ArrowWorker . '/Client/Http/Http' . self::CLASS_EXT,
            'ArrowWorker\Client\Http\Http2' => ArrowWorker . '/Client/Http/Http2' . self::CLASS_EXT,

            'ArrowWorker\Client\Tcp\Pool'   => ArrowWorker . '/Client/Tcp/Pool' . self::CLASS_EXT,
            'ArrowWorker\Client\Tcp\Client' => ArrowWorker . '/Client/Tcp/Client' . self::CLASS_EXT,

            'ArrowWorker\Client\GRpc\Pool'   => ArrowWorker . '/Client/GRpc/Pool' . self::CLASS_EXT,
            'ArrowWorker\Client\GRpc\Client' => ArrowWorker . '/Client/GRpc/Client' . self::CLASS_EXT,

            'ArrowWorker\Component\View'    => ArrowWorker . '/Component/View' . self::CLASS_EXT,
            'ArrowWorker\Component\Worker'  => ArrowWorker . '/Component/Worker' . self::CLASS_EXT,

            'ArrowWorker\Component\Db\Db'         => ArrowWorker . '/Component/Db/Db' . self::INTERFACE_EXT,
            'ArrowWorker\Component\Db\Mysqli'     => ArrowWorker . '/Component/Db/Mysqli' . self::CLASS_EXT,
            'ArrowWorker\Component\Db\Pdo'        => ArrowWorker . '/Component/Db/Pdo' . self::CLASS_EXT,
            'ArrowWorker\Component\Db\Pool'       => ArrowWorker . '/Component/Db/Pool' . self::CLASS_EXT,
            'ArrowWorker\Component\Db\SqlBuilder' => ArrowWorker . '/Component/Db/SqlBuilder' . self::CLASS_EXT,

            'ArrowWorker\Component\Cache\Cache' => ArrowWorker . '/Component/Cache/Cache' . self::INTERFACE_EXT,
            'ArrowWorker\Component\Cache\Redis' => ArrowWorker . '/Component/Cache/Redis' . self::CLASS_EXT,
            'ArrowWorker\Component\Cache\Pool'  => ArrowWorker . '/Component/Cache/Pool' . self::CLASS_EXT,

            'ArrowWorker\Component\View\Smarty'              => ArrowWorker . '/Component/View/Smarty' . self::CLASS_EXT,
            'ArrowWorker\Component\Worker\ArrowDaemon'       => ArrowWorker . '/Component/Worker/ArrowDaemon' . self::CLASS_EXT,
            'ArrowWorker\Component\Channel\Queue'            => ArrowWorker . '/Component/Channel/Queue' . self::CLASS_EXT,
            'ArrowWorker\Component\Memory\SwTable'           => ArrowWorker . '/Component/Memory/SwTable' . self::CLASS_EXT,
        ];
    }

}