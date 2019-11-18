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

    /**
     *
     */
    const POOL_ALIAS = [
        'DB'           => '\ArrowWorker\Db',
        'CACHE'        => '\ArrowWorker\Cache',
        'TCP_CLIENT'   => '\ArrowWorker\Client\Tcp\Pool',
        'WS_CLIENT'    => '\ArrowWorker\Client\Ws\Pool',
        'HTTP2_CLIENT' => '\ArrowWorker\Client\Http\Pool',

    ];

    /**
     * @var array
     */
    private $_poolComponents = [];

    public static function Init()
    {
        return new self();
    }

    public function InitCommon()
    {
        foreach ( self::BASE_COMPONENTS as $component )
        {
            $component::Init();
        }
    }

    public function InitWeb( SwRequest $request, SwResponse $response )
    {
        $this->InitCommon();
        Request::Init( $request );
        Response::Init( $response );
        Session::Init();
    }

    public function InitOpen( SwRequest $request )
    {
        $this->InitCommon();
        Request::Init( $request );
    }

    /**
     * @param array $components
     */
    public function InitPool( array $components )
    {
        foreach ( $components as $key => $config )
        {
            $component = self::POOL_ALIAS[strtoupper( $key )] ?? '';
            if ( '' !== $component )
            {
                Log::Init();
                $component::Init( $config );
                $this->_poolComponents[] = $component;
            }
        }
    }

    /**
     * @param int $type
     */
    public function Release( int $type = App::TYPE_HTTP )
    {
        $components = !in_array( $type, [
                App::TYPE_HTTP,
                App::TYPE_WEBSOCKET,
            ] ) ?
            array_merge( $this->_poolComponents, self::BASE_COMPONENTS ) :
            array_merge( $this->_poolComponents, self::WEB_COMPONENTS, self::BASE_COMPONENTS );
        foreach ( $components as $component )
        {
            $component::Release();
        }
    }

}