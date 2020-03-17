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
		$this->container->Get( App::class, [ $this->container ] )->Run();
	}
	
	private function initContainer()
	{
		$this->container = $container = new Container();
	}
	
	private function setAutoLoad()
	{
		spl_autoload_register( [
			$this,
			'LoadClass',
		] );
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
	public function LoadClass( string $class )
	{
		$fileMap = $this->_GetAutoLoadAlias();
		$class   = $fileMap[ $class ] ?? dirname( __DIR__ ) . DIRECTORY_SEPARATOR . str_replace( '\\', "/", $class );
		$class   .= self::EXT;
		if ( !file_exists( $class ) )
		{
			Log::Dump( "{$class} not found ", Log::TYPE_NOTICE, 'AutoLoad' );
			return;
		}
		require $class;
	}
	
	
	/**
	 * @return array
	 */
	private function _GetAutoLoadAlias()
	{
		return [
			'ArrowWorker\App'       => ArrowWorker . '/App',
			'ArrowWorker\Di'        => ArrowWorker . '/Di',
			'ArrowWorker\Config'    => ArrowWorker . '/Config',
			'ArrowWorker\Exception' => ArrowWorker . '/Exception',
			
			'ArrowWorker\PoolInterface' => ArrowWorker . '/PoolInterface',
			'ArrowWorker\Console'       => ArrowWorker . '/Console',
			'ArrowWorker\Daemon'        => ArrowWorker . '/Daemon',
			
			'ArrowWorker\Log'       => ArrowWorker . '/Log',
			'ArrowWorker\Chan'      => ArrowWorker . '/Chan',
			'ArrowWorker\Memory'    => ArrowWorker . '/Memory',
			'ArrowWorker\Component' => ArrowWorker . '/Component',
			
			'ArrowWorker\Web\Session'  => ArrowWorker . '/Web/Session',
			'ArrowWorker\Web\Response' => ArrowWorker . '/Web/Response',
			'ArrowWorker\Web\Request'  => ArrowWorker . '/Web/Request',
			'ArrowWorker\Web\Router'   => ArrowWorker . '/Web/Router',
			'ArrowWorker\Web\Upload'   => ArrowWorker . '/Web/Upload',
			
			'ArrowWorker\Library\Snowflake'              => ArrowWorker . '/Library/Snowflake',
			'ArrowWorker\Library\Locker'                 => ArrowWorker . '/Library/Locker',
			'ArrowWorker\Library\Bytes'                  => ArrowWorker . '/Library/Bytes',
			'ArrowWorker\Library\Coroutine'              => ArrowWorker . '/Library/Coroutine',
			'ArrowWorker\Library\Context'                => ArrowWorker . '/Library/Context',
			'ArrowWorker\Library\Channel'                => ArrowWorker . '/Library/Channel',
			'ArrowWorker\Library\Process'                => ArrowWorker . '/Library/Process',
			'ArrowWorker\Library\Crypto\CryptoArrow'     => ArrowWorker . '/Library/Crypto/CryptoArrow',
			'ArrowWorker\Library\Validation\ValidateImg' => ArrowWorker . '/Library/Validation/ValidateImg',
			
			'ArrowWorker\Library\Image\Gd'                => ArrowWorker . '/Library/Image/Gd',
			'ArrowWorker\Library\Image\ImageMagick'       => ArrowWorker . '/Library/Image/ImageMagick',
			'ArrowWorker\Library\Image\Image'             => ArrowWorker . '/Library/Image/Image',
			'ArrowWorker\Library\Image\ImageInterface'    => ArrowWorker . '/Library/Image/ImageInterface',
			'ArrowWorker\Library\Image\Gif\GifHelper'     => ArrowWorker . '/Library/Image/Gif/GifHelper',
			'ArrowWorker\Library\Image\Gif\GifByteStream' => ArrowWorker . '/Library/Image/Gif/GifByteStream',
			'ArrowWorker\Library\System\LoadAverage'      => ArrowWorker . '/Library/System/LoadAverage',
			
			'ArrowWorker\Library\Xml\Writer'    => ArrowWorker . '/Library/Xml/Writer',
			'ArrowWorker\Library\Xml\Reader'    => ArrowWorker . '/Library/Xml/Reader',
			'ArrowWorker\Library\Xml\Converter' => ArrowWorker . '/Library/Xml/Converter',
			
			'ArrowWorker\Server\Server' => ArrowWorker . '/Server/Server',
			'ArrowWorker\Server\Http'   => ArrowWorker . '/Server/Http',
			'ArrowWorker\Server\Ws'     => ArrowWorker . '/Server/Ws',
			'ArrowWorker\Server\Tcp'    => ArrowWorker . '/Server/Tcp',
			'ArrowWorker\Server\Udp'    => ArrowWorker . '/Server/Udp',
			'ArrowWorker\Server\GRpc'   => ArrowWorker . '/Server/GRpc',
			
			'ArrowWorker\Client\Ws\Pool'   => ArrowWorker . '/Client/Ws/Pool',
			'ArrowWorker\Client\Ws\Client' => ArrowWorker . '/Client/Ws/Client',
			
			'ArrowWorker\Client\Http\Pool'  => ArrowWorker . '/Client/Http/Pool',
			'ArrowWorker\Client\Http\Http'  => ArrowWorker . '/Client/Http/Http',
			'ArrowWorker\Client\Http\Http2' => ArrowWorker . '/Client/Http/Http2',
			
			'ArrowWorker\Client\Tcp\Pool'   => ArrowWorker . '/Client/Tcp/Pool',
			'ArrowWorker\Client\Tcp\Client' => ArrowWorker . '/Client/Tcp/Client',
			
			'ArrowWorker\Client\GRpc\Pool'   => ArrowWorker . '/Client/GRpc/Pool',
			'ArrowWorker\Client\GRpc\Client' => ArrowWorker . '/Client/GRpc/Client',
			
			'ArrowWorker\Component\View'   => ArrowWorker . '/Component/View',
			
			'ArrowWorker\Component\Db\DbInterface' => ArrowWorker . '/Component/Db/DbInterface',
			'ArrowWorker\Component\Db\Mysqli'      => ArrowWorker . '/Component/Db/Mysqli',
			'ArrowWorker\Component\Db\Pdo'         => ArrowWorker . '/Component/Db/Pdo',
			'ArrowWorker\Component\Db\Pool'        => ArrowWorker . '/Component/Db/Pool',
			'ArrowWorker\Component\Db\Query'       => ArrowWorker . '/Component/Db/Query',
			
			'ArrowWorker\Component\Cache\CacheInterface' => ArrowWorker . '/Component/Cache/CacheInterface',
			'ArrowWorker\Component\Cache\Redis'          => ArrowWorker . '/Component/Cache/Redis',
			'ArrowWorker\Component\Cache\Pool'           => ArrowWorker . '/Component/Cache/Pool',
			
			'ArrowWorker\Component\View\Smarty'    => ArrowWorker . '/Component/View/Smarty',
			'ArrowWorker\Component\Worker\Arrow'   => ArrowWorker . '/Component/Worker/Arrow',
			'ArrowWorker\Component\Channel\Queue'  => ArrowWorker . '/Component/Channel/Queue',
			'ArrowWorker\Component\Memory\SwTable' => ArrowWorker . '/Component/Memory/SwTable',
		];
	}
	
}