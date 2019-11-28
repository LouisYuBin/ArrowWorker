<?php
/**
 * Project ArrowWorker
 * User:   louis
 * Date:   18-1-1
 */

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

    private static $_get = [];

    private static $_post = [];

    private static $_server = [];

    private static $_file = [];

    /**
     * @var array
     */
    private static $_header = [];

    /**
     * @var array
     */
    private static $_raw = [];

    private static $_routeType = [];

    /**
     * Init : init request data(post/get/files...)
     * @param SwRequest $request
     */
    public static function Init( SwRequest $request )
    {
        $coId                   = Coroutine::Id();
        self::$_get[ $coId ]    = is_array( $request->get ) ? $request->get : [];
        self::$_post[ $coId ]   = is_array( $request->post ) ? $request->post : [];
        self::$_file[ $coId ]   = is_array( $request->files ) ? $request->files : [];
        self::$_server[ $coId ] = is_array( $request->server ) ? $request->server : [];
        self::$_raw[ $coId ]    = $request->rawContent();
        self::$_header[ $coId ] = is_array( $request->header ) ? $request->header : [];
        self::$_params[ $coId ] = [];

        Cookie::Init( is_array( $request->cookie ) ? $request->cookie : [] );

        self::InitUrlPostParams();

    }

    private static function InitUrlPostParams()
    {
        $coId = Coroutine::Id();

        if ( count( self::$_post[ $coId ] ) > 0 )
        {
            return;
        }

        $raw = self::$_raw[ $coId ];
        if ( empty( $raw ) )
        {
            return;
        }

        // normal x-www-form-urlencoded data
        if ( substr( $raw, 0, 1 ) != '{' )
        {
            parse_str( $raw, $postParam );
            self::$_post[ $coId ] = $postParam;
        }
        else // json data
        {
            $postParam = json_decode( $raw, true );
            if ( is_array( $postParam ) )
            {
                self::$_post[ $coId ] = $postParam;
            }
        }
    }

    /**
     * Method:return current request method(get/post/put/delete...)
     * @return string
     */
    public static function Method() : string
    {
        return self::$_server[ Coroutine::Id() ][ 'request_method' ];
    }

    /**
     * @return string
     */
    public static function Uri() : string
    {
        return self::$_server[ Coroutine::Id() ][ 'request_uri' ];
    }


    /**
     * @return string
     */
    public static function Raw() : string
    {
        return self::$_raw[ Coroutine::Id() ];
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
        return self::$_server[ Coroutine::Id() ][ 'query_string' ];
    }

    /**
     * @return string
     */
    public static function UserAgent() : string
    {
        return self::$_header[ Coroutine::Id() ][ 'user-agent' ];
    }


    /**
     * @return string
     */
    public static function ClientIp() : string
    {
        return self::$_server[ Coroutine::Id() ][ 'remote_addr' ];
    }

    /**
     * Get : return specified get data
     *
     * @param string $key
     *
     * @return string|bool
     */
    public static function Get( string $key ) : string
    {
        return isset( self::$_get[ Coroutine::Id() ][ $key ] ) ? self::$_get[ Coroutine::Id() ][ $key ] : '';
    }

    /**
     * @param string $key
     * @return string
     */
    public static function Post( string $key ) : string
    {
        return ( !isset( self::$_post[ Coroutine::Id() ][ $key ] ) ) ? '' : self::$_post[ Coroutine::Id() ][ $key ];
    }


    /**
     * @param string $key
     * @return string
     */
    public static function Param( string $key ) : string
    {
        return ( !isset( self::$_params[ Coroutine::Id() ][ $key ] ) ) ? '' :
            self::$_params[ Coroutine::Id() ][ $key ];
    }

    /**
     * Params : return specified post data
     * @return array
     */
    public static function Params() : array
    {
        return self::$_params[ Coroutine::Id() ];
    }

    /**
     * Header : return specified post data
     *
     * @param string $key
     *
     * @return string
     */
    public static function Header( string $key ) : string
    {
        return ( !isset( self::$_header[ Coroutine::Id() ][ $key ] ) ) ? '' :
            self::$_header[ Coroutine::Id() ][ $key ];
    }

    /**
     * Headers : return specified post data
     * @return array
     */
    public static function Headers() : array
    {
        return self::$_header[ Coroutine::Id() ];
    }

    /**
     * Gets : return all get data
     * @return array
     */
    public static function Gets() : array
    {
        return self::$_get[ Coroutine::Id() ];
    }

    /**
     * Posts : return all post data
     * @return array
     */
    public static function Posts() : array
    {
        return self::$_post[ Coroutine::Id() ];
    }

    /**
     * Server : return specified server data
     *
     * @param string $key
     *
     * @return string|bool
     */
    public static function Server( string $key )
    {
        return ( !isset( self::$_server[ Coroutine::Id() ][ $key ] ) ) ? false : self::$_server[ Coroutine::Id() ][ $key ];
    }

    /**
     * Servers : return all server data
     * @return array
     */
    public static function Servers()
    {
        return self::$_server[ Coroutine::Id() ];
    }

    /**
     * Servers : return all server data
     *
     * @param string $name
     *
     * @return Upload|false
     */
    public static function File( string $name )
    {
        return !isset( self::$_file[ Coroutine::Id() ][ $name ] ) ?
            false :
            new Upload( (array)self::$_file[ Coroutine::Id() ][ $name ] );
    }

    /**
     * Servers : return all server data
     * @return array
     */
    public static function Files()
    {
        return self::$_file[ Coroutine::Id() ];
    }

    /**
     * @param array  $params
     * @param string $routeType path/rest
     */
    public static function SetParams( array $params, string $routeType = 'path' )
    {
        self::$_params[ Coroutine::Id() ]    = $params;
        self::$_routeType[ Coroutine::Id() ] = $routeType;

        self::_logRequest();
    }

    /**
     * release resource for request
     */
    public static function Release()
    {
        $coId = Coroutine::Id();
        unset( self::$_get[ $coId ], self::$_post[ $coId ], self::$_file[ $coId ], self::$_server[ $coId ], self::$_params[ $coId ], self::$_header[ $coId ], static::$_raw[ $coId ], static::$_routeType[ $coId ], $coId );
    }

    private static function _logRequest()
    {
        $coId   = Coroutine::Id();
        $uri    = self::Uri();
        $raw    = self::Raw();
        $method = self::Method();
        $params = json_encode( self::$_params[ $coId ], JSON_UNESCAPED_UNICODE );
        $get    = json_encode( self::$_get[ $coId ], JSON_UNESCAPED_UNICODE );
        $post   = json_encode( self::$_post[ $coId ], JSON_UNESCAPED_UNICODE );
        $files  = json_encode( self::$_file[ $coId ], JSON_UNESCAPED_UNICODE );
        $server = json_encode( self::$_server[ $coId ], JSON_UNESCAPED_UNICODE );
        $header = json_encode( self::$_header[ $coId ], JSON_UNESCAPED_UNICODE );

        $routeType = self::RouteType();

        Log::Debug( " {$uri} [{$method}:$routeType]   Params : {$params}   Get : {$get}   Post : {$post}   Header : {$header}   Server : {$server}   raw : {$raw}   Files : {$files}", self::LOG_NAME );
        unset( $method, $get, $post, $files, $params, $header, $server );
        Cookie::Release();
    }

}