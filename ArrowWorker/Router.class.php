<?php
/**
 * User: louis
 * Date: 17-10-20
 * Time: 上午12:51
 */

namespace ArrowWorker;
use ArrowWorker\Config as config;


class Router
{
    const defaults  = 'Index';
    const routeType = "RouterType";

    const get = 1;
    const uri = 2;

    private static $routerType;
    private static $return = ['c'=> self::defaults, 'm' => self::defaults];

    private static function getRouteType()
    {
        self::$routerType = config::App(self::routeType);
    }

    public static function Get()
    {
        self::getRouteType();
        switch (self::$routerType){
            case self::get;
                // "get" 形式路由
                self::getRouter();
            break;
            case self::uri;
                // "?/类/方法" 形式路由
                self::uriRouter();
            break;
            default:
                //Todo
        }
        return self::$return;
    }

    public static function getRouter()
    {
        @self::$return['c'] = isset($_REQUEST['c']) ? $_REQUEST['c'] : self::defaults;
        @self::$return['m'] = isset($_REQUEST['m']) ? $_REQUEST['m'] : self::defaults;
    }

    public static function uriRouter()
    {

    }
}