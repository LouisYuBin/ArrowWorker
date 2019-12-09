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
	const EXT = '.php';
	
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
			$class = APP_PATH .
			         '/' .
			         str_replace( [
				         '\\',
				         explode( '\\', $class )[ 0 ],
			         ], "/", $class ) .
			         self::EXT;
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
			'ArrowWorker\App'       => ArrowWorker . '/App' . self::EXT,
			'ArrowWorker\Di'        => ArrowWorker . '/Di' . self::EXT,
			'ArrowWorker\Config'    => ArrowWorker . '/Config' . self::EXT,
			'ArrowWorker\Exception' => ArrowWorker . '/Exception' . self::EXT,
			
			'ArrowWorker\PoolInterface' => ArrowWorker . '/PoolInterface' . self::EXT,
			'ArrowWorker\Console'       => ArrowWorker . '/Console' . self::EXT,
			'ArrowWorker\Daemon'        => ArrowWorker . '/Daemon' . self::EXT,
			
			'ArrowWorker\Log'       => ArrowWorker . '/Log' . self::EXT,
			'ArrowWorker\Worker'    => ArrowWorker . '/Worker' . self::EXT,
			'ArrowWorker\Db'        => ArrowWorker . '/Db' . self::EXT,
			'ArrowWorker\Chan'      => ArrowWorker . '/Chan' . self::EXT,
			'ArrowWorker\Memory'    => ArrowWorker . '/Memory' . self::EXT,
			'ArrowWorker\Component' => ArrowWorker . '/Component' . self::EXT,
			'ArrowWorker\Cache'     => ArrowWorker . '/Cache' . self::EXT,
			
			'ArrowWorker\Web\Session'  => ArrowWorker . '/Web/Session' . self::EXT,
			'ArrowWorker\Web\Response' => ArrowWorker . '/Web/Response' . self::EXT,
			'ArrowWorker\Web\Request'  => ArrowWorker . '/Web/Request' . self::EXT,
			'ArrowWorker\Web\Router'   => ArrowWorker . '/Web/Router' . self::EXT,
			'ArrowWorker\Web\Upload'   => ArrowWorker . '/Web/Upload' . self::EXT,
			
			'ArrowWorker\Library\Bytes'                   => ArrowWorker . '/Library/Crypto/Bytes' . self::EXT,
			'ArrowWorker\Library\Coroutine'               => ArrowWorker . '/Library/Coroutine' . self::EXT,
			'ArrowWorker\Library\Channel'                 => ArrowWorker . '/Library/Channel' . self::EXT,
			'ArrowWorker\Library\Process'                 => ArrowWorker . '/Library/Process' . self::EXT,
			'ArrowWorker\Library\Crypto\CryptoArrow'      => ArrowWorker . '/Library/Crypto/CryptoArrow' . self::EXT,
			'ArrowWorker\Library\Validation\ValidateImg'  => ArrowWorker . '/Library/Validation/ValidateImg' .
			                                                 self::EXT,
			'ArrowWorker\Library\Image\Gd'                => ArrowWorker . '/Library/Image/Gd' . self::EXT,
			'ArrowWorker\Library\Image\ImageMagick'       => ArrowWorker . '/Library/Image/ImageMagick' . self::EXT,
			'ArrowWorker\Library\Image\Image'             => ArrowWorker . '/Library/Image/Image' . self::EXT,
			'ArrowWorker\Library\Image\ImageInterface'    => ArrowWorker . '/Library/Image/ImageInterface' . self::EXT,
			'ArrowWorker\Library\Image\Gif\GifHelper'     => ArrowWorker . '/Library/Image/Gif/GifHelper' . self::EXT,
			'ArrowWorker\Library\Image\Gif\GifByteStream' => ArrowWorker . '/Library/Image/Gif/GifByteStream' .  self::EXT,
			'ArrowWorker\Library\System\LoadAverage'      => ArrowWorker . '/Library/System/LoadAverage' . self::EXT,
			
			'ArrowWorker\Library\Xml\Writer'    => ArrowWorker . '/Library/Xml/Writer' . self::EXT,
			'ArrowWorker\Library\Xml\Reader'    => ArrowWorker . '/Library/Xml/Reader' . self::EXT,
			'ArrowWorker\Library\Xml\Converter' => ArrowWorker . '/Library/Xml/Converter' . self::EXT,
			
			'ArrowWorker\Server\Server' => ArrowWorker . '/Server/Server' . self::EXT,
			'ArrowWorker\Server\Http'   => ArrowWorker . '/Server/Http' . self::EXT,
			'ArrowWorker\Server\Ws'     => ArrowWorker . '/Server/Ws' . self::EXT,
			'ArrowWorker\Server\Tcp'    => ArrowWorker . '/Server/Tcp' . self::EXT,
			'ArrowWorker\Server\Udp'    => ArrowWorker . '/Server/Udp' . self::EXT,
			'ArrowWorker\Server\GRpc'   => ArrowWorker . '/Server/GRpc' . self::EXT,
			
			'ArrowWorker\Client\Ws\Pool'   => ArrowWorker . '/Client/Ws/Pool' . self::EXT,
			'ArrowWorker\Client\Ws\Client' => ArrowWorker . '/Client/Ws/Client' . self::EXT,
			
			'ArrowWorker\Client\Http\Pool'  => ArrowWorker . '/Client/Http/Pool' . self::EXT,
			'ArrowWorker\Client\Http\Http'  => ArrowWorker . '/Client/Http/Http' . self::EXT,
			'ArrowWorker\Client\Http\Http2' => ArrowWorker . '/Client/Http/Http2' . self::EXT,
			
			'ArrowWorker\Client\Tcp\Pool'   => ArrowWorker . '/Client/Tcp/Pool' . self::EXT,
			'ArrowWorker\Client\Tcp\Client' => ArrowWorker . '/Client/Tcp/Client' . self::EXT,
			
			'ArrowWorker\Client\GRpc\Pool'   => ArrowWorker . '/Client/GRpc/Pool' . self::EXT,
			'ArrowWorker\Client\GRpc\Client' => ArrowWorker . '/Client/GRpc/Client' . self::EXT,
			
			'ArrowWorker\Component\View'   => ArrowWorker . '/Component/View' . self::EXT,
			'ArrowWorker\Component\Worker' => ArrowWorker . '/Component/Worker' . self::EXT,
			
			'ArrowWorker\Component\Db\DbInterface' => ArrowWorker . '/Component/Db/DbInterface' . self::EXT,
			'ArrowWorker\Component\Db\Mysqli'      => ArrowWorker . '/Component/Db/Mysqli' . self::EXT,
			'ArrowWorker\Component\Db\Pdo'         => ArrowWorker . '/Component/Db/Pdo' . self::EXT,
			'ArrowWorker\Component\Db\Pool'        => ArrowWorker . '/Component/Db/Pool' . self::EXT,
			'ArrowWorker\Component\Db\SqlBuilder'  => ArrowWorker . '/Component/Db/SqlBuilder' . self::EXT,
			
			'ArrowWorker\Component\Cache\CacheInterface' => ArrowWorker . '/Component/Cache/CacheInterface' . self::EXT,
			'ArrowWorker\Component\Cache\Redis'          => ArrowWorker . '/Component/Cache/Redis' . self::EXT,
			'ArrowWorker\Component\Cache\Pool'           => ArrowWorker . '/Component/Cache/Pool' . self::EXT,
			
			'ArrowWorker\Component\View\Smarty'        => ArrowWorker . '/Component/View/Smarty' . self::EXT,
			'ArrowWorker\Component\Worker\ArrowDaemon' => ArrowWorker . '/Component/Worker/ArrowDaemon' . self::EXT,
			'ArrowWorker\Component\Channel\Queue'      => ArrowWorker . '/Component/Channel/Queue' . self::EXT,
			'ArrowWorker\Component\Memory\SwTable'     => ArrowWorker . '/Component/Memory/SwTable' . self::EXT,
		];
	}
	
}