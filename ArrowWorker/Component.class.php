<?php
/**
 * By yubin at 2019-09-02 17:35.
 */

namespace ArrowWorker;

use ArrowWorker\Web\Upload;
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
        '\ArrowWorker\Web\Response',
    ];

    /**
     *
     */
    const BASE_COMPONENTS = [
        '\ArrowWorker\Log',
        '\ArrowWorker\Library\Coroutine',
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
    private $_components = [];

    public static function Init(int $type)
    {
        return new self($type);
    }

    private function __construct(int $type)
    {
        $this->_components = !in_array( $type, [
                                            App::TYPE_HTTP,
                                            App::TYPE_WEBSOCKET,
                                        ] ) ?
                            self::BASE_COMPONENTS :
                            array_merge(self::WEB_COMPONENTS, self::BASE_COMPONENTS );
    }

    public function InitCommon()
    {
        foreach ( self::BASE_COMPONENTS as $component )
        {
            $component::Init();
        }
    }

    public function InitWebRequest( SwRequest $request, SwResponse $response )
    {
        $this->InitCommon();
        Request::Init( $request );
        Response::Init( $response );
    }

    public function InitWebWorkerStart(array $components, bool $isEnableCORS)
    {
        Session::Init();
        Upload::Init();
        $this->InitPool($components);
        Response::SetCORS( $isEnableCORS );
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
        Log::Init();
        foreach ( $components as $key => $config )
        {
            $component = self::POOL_ALIAS[strtoupper( $key )] ?? '';
            if ( '' !== $component )
            {
                $component::Init( $config );
                $this->_components[] = $component;
            }
        }
    }

    public function Release()
    {
        foreach ( $this->_components as $component )
        {
            $component::Release();
        }
    }

}