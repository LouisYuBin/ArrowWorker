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
use ArrowWorker\Library\Coroutine as Co;


/**
 * Class Request
 * @package ArrowWorker
 */
class Request
{
	
	const LOG_NAME = 'Http';
	
	/**
	 * Init : init request data(post/get/files...)
	 * @param SwRequest $request
	 */
	public static function Init( SwRequest $request )
	{
		Co::GetContext()[ __CLASS__ ]  = $request;
		self::InitUrlPostParams();
	}
	
	private static function InitUrlPostParams()
	{
		$request = Co::GetContext()[ __CLASS__ ];
		
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
		return Co::GetContext()[ __CLASS__ ]->server[ 'request_method' ];
	}
	
	/**
	 * @return string
	 */
	public static function Uri() : string
	{
		return Co::GetContext()[ __CLASS__ ]->server[ 'request_uri' ];
	}
	
	/**
	 * @return string
	 */
	public static function Raw() : string
	{
		return Co::GetContext()[ __CLASS__ ]->rawContent();
	}
	
	/**
	 * @return string
	 */
	public static function RouteType() : string
	{
		return Co::GetContext()['routerType'] ?? '';
	}
	
	/**
	 * @return string
	 */
	public static function QueryString() : string
	{
		return Co::GetContext()[ __CLASS__ ]->server[ 'query_string' ];
	}
	
	/**
	 * @return string
	 */
	public static function UserAgent() : string
	{
		return Co::GetContext()[__CLASS__]->header[ 'user-agent' ];
	}
	
	
	/**
	 * @return string
	 */
	public static function ClientIp() : string
	{
		return Co::GetContext()[__CLASS__]->server[ 'remote_addr' ];
	}
	
	/**
	 * @param string $key
	 * @param string $default
	 * @return string|bool
	 */
	public static function Get( string $key, string $default='' ) : string
	{
		return Co::GetContext()[__CLASS__]->get[ $key ] ?? $default;
	}
	
	/**
	 * @param string $key
	 * @param string $default
	 * @return string
	 */
	public static function Post( string $key, string $default='' ) : string
	{
		return Co::GetContext()[__CLASS__]->post[ $key ] ?? $default;
	}
	
	public static function Cookie( string $key, string $default='' ) : string
	{
		return Co::GetContext()[__CLASS__]->cookie[ $key ] ?? $default;
	}
	
	/**
	 * @param string $key
	 * @param string $default
	 * @return string
	 */
	public static function Param( string $key, string $default='' ) : string
	{
		return Co::GetContext()['urlParameters'][ $key ] ?? $default;
	}
	
	/**
	 * Params : return specified post data
	 * @return array
	 */
	public static function Params() : array
	{
		return Co::GetContext()['urlParameters'] ?? [];
	}
	
	/**
	 * @param string $key
	 * @param string $default
	 * @return string
	 */
	public static function Header( string $key,string $default='' ) : string
	{
		return Co::GetContext()[__CLASS__]->header[ $key ] ?? $default;
	}
	
	public static function Host() : string
	{
		return Co::GetContext()[__CLASS__]->header[ 'host' ];
	}
	
	/**
	 * @return array
	 */
	public static function Headers() : array
	{
		return (array)Co::GetContext()[__CLASS__]->header;
	}
	
	/**
	 * Gets : return all get data
	 * @return array
	 */
	public static function Gets() : array
	{
		return (array)Co::GetContext()[__CLASS__]->get;
	}
	
	/**
	 * @return array
	 */
	public static function Posts() : array
	{
		return (array)Co::GetContext()[__CLASS__]->post;
	}
	
	/**
	 * @param string $key
	 * @return string
	 */
	public static function Server( string $key ) : string
	{
		return Co::GetContext()[__CLASS__]->server[ $key ] ?? '';
	}
	
	/**
	 * @return array
	 */
	public static function Servers()
	{
		return (array)Co::GetContext()[__CLASS__]->server;
	}
	
	/**
	 * @param string $name
	 * @return Upload|false
	 */
	public static function File( string $name )
	{
		$file = Co::GetContext()[__CLASS__]->files[ $name ];
		return is_null( $file ) ?
			false :
			new Upload( (array)$file );
	}
	
	/**
	 * @return array
	 */
	public static function Files() : array
	{
		return (array)Co::GetContext()[__CLASS__]->files;
	}
	
	/**
	 * @param array  $params
	 * @param string $routeType path/rest
	 */
	public static function SetParams( array $params, string $routeType = 'path' )
	{
		$context = Co::GetContext();
		$context['urlParameters'] = $params;
		$context['routerType'] = $routeType;
		//self::_logRequest( );
	}
	
	public static function Release()
	{

	}
	
	private static function _logRequest( )
	{
		$context   = Co::GetContext();
		$request   = $context[__CLASS__];
		$params    = json_encode( $context['urlParameters'], JSON_UNESCAPED_UNICODE );
		
		Log::Debug( " {$request->server[ 'request_uri' ]} [{$request->server[ 'request_method' ]}:{$context['routerType']}]   Params : {$params} , Request : " .
		            json_encode( $request, JSON_UNESCAPED_UNICODE ), self::LOG_NAME );
		unset($params);
	}
	
}