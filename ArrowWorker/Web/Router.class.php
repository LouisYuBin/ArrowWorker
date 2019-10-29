<?php
/**
 * User: louis
 * Date: 17-10-20
 * Time: 上午12:51
 */

namespace ArrowWorker\Web;

use ArrowWorker\Config;
use ArrowWorker\Log;

/**
 * Class Router
 * @package ArrowWorker
 */
class Router
{
    const LOG_NAME = 'router';

    /**
     *
     */
    const CONTROLLER_NAMESPACE = '\\'.APP_DIR.'\\'.APP_CONTROLLER_DIR.'\\';

    /**
     * @var array
     */
    private static $_restApiConfig = [];

    /**
     * @var array
     */
    private static $_pregAlias = [];

    /**
     * @var string
     */
    private static $_404 = 'page not found(该页面不存在).';

    /**
     * @param string $_404
     */
    public static function Init(string $_404)
    {
        self::_loadRestConfig();
        self::_analyseUri();
        self::_load404($_404);
    }

    /**
     *
     */
    private static function _loadRestConfig()
    {
        $config = Config::Get('WebRouter');
        if( false===$config )
        {
            Log::Warning("Load rest api configuration failed");
            return ;
        }
        if( !is_array($config) )
        {
            Log::Warning(" rest api configuration format is incorrect.");
            return ;
        }

        foreach ($config as $serverNames=>$restMap)
        {
            $serverNameArray = explode(',', $serverNames);
            foreach ( $serverNameArray as $serverName)
            {
                static::$_restApiConfig[trim($serverName)] = $restMap;
            }
        }

    }

    /**
     *
     */
    private static function _analyseUri()
    {
        foreach (static::$_restApiConfig as $serverName=>$restMap)
        {
            foreach ($restMap as $uri=>$alias)
            {
                $nodes    = explode('/', $uri);
                $match    = preg_replace(['/:\w+/','/\//'], ['[a-zA-Z0-9_-]+','\\/'], $uri);
                $colonPos = strpos($uri, ':');
                $key      = (false===$colonPos) ? $uri : substr($uri, 0, $colonPos-1);
                $params   = [];
                foreach ($nodes as $index=>$param)
                {
                    if( false===strpos($param, ':') )
                    {
                        continue;
                    }
                    $params[$index] = str_replace(':', '', $param);
                }
                static::$_pregAlias[$serverName][$key]["/^{$match}$/"] = [
                    'uri'    => $uri,
                    'params' => $params
                ];
            }
        }

    }

    /**
     * @return string
     */
    public static function _getRestUriKey() : string
    {
        $uri        = Request::Uri();
        $nodes      = explode('/', $uri);
        $nodeLen    = count($nodes);
        $serverName = Request::Header('host');

        for($i=$nodeLen; $i>1; $i--)
        {
            $key = '/'.implode('/', array_slice($nodes,1, $i-1));
            if( !isset(static::$_pregAlias[$serverName][$key]) )
            {
                continue ;
            }

            $nodeMap = static::$_pregAlias[$serverName][$key];
            foreach ( $nodeMap as $match=>$eachNode )
            {
                $isMatched = preg_match($match, $uri);
                if( false===$isMatched || $isMatched===0)
                {
                    continue ;
                }

                //获取对应参数值
                $params = [];
                foreach ($eachNode['params'] as $index=>$param)
                {
                    $params[$param] = $nodes[$index];
                }
                Request::SetParams($params, 'REST');
                return $eachNode['uri'];
            }
        }
        return '';
    }


	/**
	 * Exec 返回要调用的控制器和方法
	 */
	public static function Exec()
    {
        if( static::_restRouter() )
        {
            return ;
        }

        if( static::_pathRouter() )
        {
            return ;
        }

        static::_logAndResponse("request does not match any router");
    }

    /**
     * @return bool
     */
    private static function _restRouter()
    {
        $key        = static::_getRestUriKey();
        $method     = strtolower(Request::Method());
        $serverName = Request::Header('host');

        if( empty($key) )
        {
            return false;
        }

        if( !isset(static::$_restApiConfig[$serverName][$key][$method]) )
        {
            return false;
        }

        list($class, $function) = explode('::', static::$_restApiConfig[$serverName][$key][$method]);
        $class = self::CONTROLLER_NAMESPACE.$class;
        return static::_routeToFunction($class, $function);
    }

    /**
     * @return bool
     */
    private static function _pathRouter()
    {
        $uri      = Request::Uri();
        $pathInfo = explode('/', $uri);
        $pathLen  = count($pathInfo);
        Request::SetParams([], 'PATH');

        if( $pathLen<3 )
        {
            return false;
        }

        if( $pathLen==4 && $pathInfo[1]!='' && $pathInfo[2]!='' && $pathInfo[3]!='' )
        {
            $class = self::CONTROLLER_NAMESPACE.$pathInfo[1].'\\'.$pathInfo[2];
            return static::_routeToFunction($class, $pathInfo[3]);
        }

        if( $pathLen>=3 && $pathInfo[1]!='' && $pathInfo[2]!='' )
        {
            $class = self::CONTROLLER_NAMESPACE.$pathInfo[1];
            return static::_routeToFunction($class, $pathInfo[2]);
        }

        return false;
    }


    /**
     * @param string $class
     * @param string $function
     *
     * @return bool
     */
    private static function _routeToFunction(string $class, string $function)
    {
        if( !class_exists($class) )
        {
            return static::_logAndResponse("controller class : {$class} does not exists.");
        }

        $controller = new $class;
        if( !method_exists($controller, $function) )
        {
            return static::_logAndResponse("controller function : {$class}->{$function} does not exists.");
        }
        $controller->$function();
        unset($controller);
        return true;
    }

    /**
     * @param string $msg
     *
     * @return bool
     */
    private static function _logAndResponse(string $msg)
    {
        Log::Warning($msg, self::LOG_NAME);
        if( !DEBUG )
        {
            $msg = static::$_404;
        }
        Response::Write($msg);
        return true;
    }

    /**
     * @param string $_404
     */
    private static function _load404(string $_404)
    {
        if( empty($_404) || !file_exists($_404) )
        {
            static::$_404 = file_get_contents(ArrowWorker.'/Static/404.html');
            return ;
        }

        static::$_404 = file_get_contents($_404);
    }

}