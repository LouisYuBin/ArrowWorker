<?php
/**
 * By yubin at 2019-09-02 17:35.
 */

namespace ArrowWorker;

use Swoole\Http\Request as SwRequest;
use Swoole\Http\Response as SwResponse;

use ArrowWorker\Web\Request;
use ArrowWorker\Web\Response;
use ArrowWorker\Web\Session;

/**
 * Class Component
 * @package ArrowWorker
 */
class Component
{
    /**
     *
     */
    const WEB_COMPONENTS = [
        '\ArrowWorker\Web\Request',
        '\ArrowWorker\Web\Session',
        '\ArrowWorker\Web\Response',
    ];

    /**
     *
     */
    const BASE_COMPONENTS = [
        '\ArrowWorker\Log',
        '\ArrowWorker\Lib\Coroutine',
    ];

    private static $_poolComponents = [];

    public static function Init()
    {
        foreach ( self::BASE_COMPONENTS as $component)
        {
            $component::Init();
        }
    }


    public static function InitWeb(SwRequest $request, SwResponse $response)
    {
        self::Init();
        Request::Init( $request );
        Response::Init( $response );
        Session::Init();
    }

    public static function InitOpen(SwRequest $request)
    {
        self::Init();
        Request::Init( $request );
    }

    /**
     * @param array $components
     */
    public static function InitPool( array $components)
    {
        foreach ( $components as $key=>$config )
        {
            switch ( strtoupper($key) )
            {
                case 'DB':
                    $component = '\ArrowWorker\Db';
                    break;
                case 'CACHE':
                    $component = '\ArrowWorker\Cache';
                    break;
                case 'TCP_CLIENT':
                    $component = '\ArrowWorker\Client\Tcp\Pool';
                    break;
                case 'WS_CLIENT':
                    $component = '\ArrowWorker\Client\Ws\Pool';
                    break;
                case 'HTTP2_CLIENT':
                    $component = '\ArrowWorker\Client\Http\Pool';
                    break;
                default:
                    $component = '';
            }

            if( ''!==$component )
            {
                Log::Init();
                $component::Init($config);
                self::$_poolComponents[] = $component;
            }
        }
    }

    /**
     * @param int $type
     */
    public static function Release( int $type=App::TYPE_HTTP)
    {
        $components = !in_array($type,[App::TYPE_HTTP, App::TYPE_WEBSOCKET]) ?
            array_merge(self::$_poolComponents, self::BASE_COMPONENTS) :
            array_merge(self::$_poolComponents, self::WEB_COMPONENTS, self::BASE_COMPONENTS);
        foreach ($components as $component)
        {
            $component::Release();
        }
    }

}