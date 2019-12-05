<?php
/**
 * Project ArrowWorker
 * User:   louis
 * Date:   18-1-1
 */

declare( strict_types=1 );


namespace ArrowWorker\Web;

use \Swoole\Http\Request as SwRequest;

use ArrowWorker\Log;
use ArrowWorker\Library\Coroutine;


/**
 * Class Request
 * @package ArrowWorker
 */
class Request
{
	
	const LOG_NAME = 'Http';
	
	/**
	 * @var array
	 */
	private static $_params = [];
	
	private static $_requests = [];
	
	private static $_routeType = [];
	
	/**
	 * Init : init request data(post/get/files...)
	 * @param SwRequest $request
	 */
	public static function Init( SwRequest $request )
	{
		$coId                      = Coroutine::Id();
		self::$_requests[ $coId ]  = $request;
		self::InitUrlPostParams();
	}
	
	private static function InitUrlPostParams()
	{
		$coId    = Coroutine::Id();
		$request = self::$_requests[ $coId ];
		
		if ( !is_null( $request->post ) )
		{
			return;
		}
		
		$raw = $request->rawContent();
		if ( empty( $raw ) )
		{
			return;
		}
		
		// normal x-www-form-urlencoded data
		if ( substr( $raw, 0, 1 ) != '{' )
		{
			parse_str( $raw, $postParam );
			$request->post = $postParam;
		}
		else // json data
		{
			$postParam = json_decode( $raw, true );
			if ( is_array( $postParam ) )
			{
				$request->post = $postParam;
			}
		}
	}
	
	/**
	 * @return string
	 */
	public static function Method() : string
	{
		return self::$_requests[ Coroutine::Id() ]->server[ 'request_method' ];
	}
	
	/**
	 * @return string
	 */
	public static function Uri() : string
	{
		return self::$_requests[ Coroutine::Id() ]->server[ 'request_uri' ];
	}
	
	/**
	 * @return string
	 */
	public static function Raw() : string
	{
		return self::$_requests[ Coroutine::Id() ]->rawContent();
	}
	
	/**
	 * @return string
	 */
	public static function RouteType() : string
	{
		return self::$_routeType[ Coroutine::Id() ];
	}
	
	/**
	 * @return string
	 */
	public static function QueryString() : string
	{
		return self::$_requests[ Coroutine::Id() ]->server[ 'query_string' ];
	}
	
	/**
	 * @return string
	 */
	public static function UserAgent() : string
	{
		return self::$_requests[ Coroutine::Id() ]->header[ 'user-agent' ];
	}
	
	
	/**
	 * @return string
	 */
	public static function ClientIp() : string
	{
		return self::$_requests[ Coroutine::Id() ]->server[ 'remote_addr' ];
	}
	
	/**
	 * @param string $key
	 * @return string|bool
	 */
	public static function Get( string $key ) : string
	{
		return self::$_requests[ Coroutine::Id() ]->get[ $key ] ?? '';
	}
	
	/**
	 * @param string $key
	 * @return string
	 */
	public static function Post( string $key ) : string
	{
		return self::$_requests[ Coroutine::Id() ]->post[ $key ] ?? '';
	}
	
	public static function Cookie( string $key ) : string
	{
		return self::$_requests[ Coroutine::Id() ]->cookie[ $key ] ?? '';
	}
	
	/**
	 * @param string $key
	 * @return string
	 */
	public static function Param( string $key ) : string
	{
		return self::$_params[ Coroutine::Id() ][ $key ] ?? '';
	}
	
	/**
	 * Params : return specified post data
	 * @return array
	 */
	public static function Params() : array
	{
		return self::$_params[ Coroutine::Id() ] ?? [];
	}
	
	/**
	 * @param string $key
	 * @return string
	 */
	public static function Header( string $key ) : string
	{
		return self::$_requests[ Coroutine::Id() ]->header[ $key ] ?? '';
	}
	
	public static function Host() : string
	{
		return self::$_requests[ Coroutine::Id() ]->header[ 'host' ];
	}
	
	/**
	 * @return array
	 */
	public static function Headers() : array
	{
		return (array)self::$_requests[ Coroutine::Id() ]->header;
	}
	
	/**
	 * Gets : return all get data
	 * @return array
	 */
	public static function Gets() : array
	{
		return (array)self::$_requests[ Coroutine::Id() ]->get;
	}
	
	/**
	 * @return array
	 */
	public static function Posts() : array
	{
		return (array)self::$_requests[ Coroutine::Id() ]->post;
	}
	
	/**
	 * @param string $key
	 * @return string
	 */
	public static function Server( string $key ) : string
	{
		return self::$_requests[ Coroutine::Id() ]->server[ $key ] ?? '';
	}
	
	/**
	 * @return array
	 */
	public static function Servers()
	{
		return (array)self::$_requests[ Coroutine::Id() ]->server;
	}
	
	/**
	 * @param string $name
	 * @return Upload|false
	 */
	public static function File( string $name )
	{
		$file = self::$_requests[ Coroutine::Id() ]->files[ $name ];
		return is_null( $file ) ?
			false :
			new Upload( (array)$file );
	}
	
	/**
	 * @return array
	 */
	public static function Files() : array
	{
		return (array)self::$_requests[ Coroutine::Id() ]->files;
	}
	
	/**
	 * @param array  $params
	 * @param string $routeType path/rest
	 */
	public static function SetParams( array $params, string $routeType = 'path' )
	{
		$coId                      = Coroutine::Id();
		self::$_params[ $coId ]    = $params;
		self::$_routeType[ $coId ] = $routeType;
	}
	
	public static function Release()
	{
		$coId = Coroutine::Id();
		self::_logRequest( $coId );
		unset( self::$_requests[ $coId ], self::$_params[ $coId ], self::$_routeType[ $coId ], $coId );
	}
	
	private static function _logRequest( int $coId )
	{
		$request   = self::$_requests[ Coroutine::Id() ];
		$uri       = $request->server[ 'request_uri' ];
		$method    = $request->server[ 'request_method' ];
		$params    = json_encode( self::$_params[ $coId ], JSON_UNESCAPED_UNICODE );
		$routeType = self::$_routeType[ $coId ];
		
		Log::Debug( " {$uri} [{$method}:{$routeType}]   Params : {$params} , Request : " .
		            json_encode( $request, JSON_UNESCAPED_UNICODE ), self::LOG_NAME );
		unset( $method, $params, $request, $routeType, $coId );
	}
	
}