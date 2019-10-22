<?php

/**
 * User: louis
 * Time: 19-10-17 下午12:38
 */

namespace ArrowWorker\Server;

use ArrowWorker\Lib\Coroutine;
use Swoole\Http\Request as SwRequest;
use Swoole\Http\Response as SwResponse;
use Swoole\Http\Server as SwHttp;

use ArrowWorker\Web\Cookie;
use ArrowWorker\Web\Request;
use ArrowWorker\Web\Response;
use ArrowWorker\Web\Router;
use ArrowWorker\Web\Session;

use ArrowWorker\Component;
use ArrowWorker\Log;



class Http
{

    /**
     * @var array
     */
    public static $defaultConfig = [
        'host'                => '0.0.0.0',
        'port'                => 8888,
        'workerNum'           => 4,
        'backlog'             => 1000,
        'user'                => 'root',
        'group'               => 'root',
        'pipeBufferSize'      => 1024 * 1024 * 100,
        'socketBufferSize'    => 1024 * 1024 * 100,
        'maxRequest'          => 20000,
        'reactorNum'          => 4,
        'maxContentLength'    => 2088960,
        'maxCoroutine'        => 10000,
        'enableCoroutine'     => true,
        'enableStaticHandler' => false,
        'isAllowCORS'         => true,
        'sslCertFile'         => '',
        'sslKeyFile'          => '',
        'documentRoot'        => '',
        'mode'                => SWOOLE_PROCESS

    ];

    /**
     * @param array $config
     * @return array
     */
    private static function _getConfig( array $config ) : array
    {

        $config = array_merge( self::$defaultConfig, $config );
        return [
            'port'                  => $config[ 'port' ],
            'worker_num'            => $config[ 'workerNum' ],
            'daemonize'             => false,
            'backlog'               => $config[ 'backlog' ],
            'user'                  => $config[ 'user' ],
            'group'                 => $config[ 'group' ],
            'package_max_length'    => $config[ 'maxContentLength' ],
            'enable_static_handler' => $config[ 'enableStaticHandler' ],
            'reactor_num'           => $config[ 'reactorNum' ],
            'pipe_buffer_size'      => $config[ 'pipeBufferSize' ],
            'socket_buffer_size'    => $config[ 'socketBufferSize' ],
            'max_request'           => $config[ 'maxRequest' ],
            'enable_coroutine'      => $config[ 'enableCoroutine' ],
            'max_coroutine'         => $config[ 'maxCoroutine' ],
            'document_root'         => $config[ 'documentRoot' ],
            'log_file'              => Log::$StdoutFile,
            'handler'               => $config[ 'handler' ],
            'ssl_cert_file'         => $config[ 'sslCertFile' ],
            'ssl_key_file'          => $config[ 'sslKeyFile' ],
            'mode'                  => $config[ 'mode' ],
            'components'            => isset( $config[ 'components' ] ) ? $config[ 'components' ] : [],
            'isAllowCORS'           => isset( $config[ 'isAllowCORS' ] ) ? (bool)$config[ 'isAllowCORS' ] : false
        ];

    }

    /**
     * @param array $config
     */
    public static function Start( array $config )
    {
        Router::Init( isset( $config[ '404' ] ) ? (string)$config[ '404' ] : '' );
        $config = static::_getConfig( $config );
        $cors   = $config[ 'isAllowCORS' ];
        $server = new SwHttp( $config[ 'host' ], $config[ 'port' ], $config[ 'mode' ], empty( $config[ 'ssl_cert_file' ] ) ? SWOOLE_SOCK_TCP : SWOOLE_SOCK_TCP |
                                                                                                                                                         SWOOLE_SSL );
        $server->set( $config );
        $server->on( 'start', function ( $server ) use ( $config )
        {
            Log::Dump( "[  Http   ] : {$config['port']} started"  );
        } );
        $server->on( 'WorkerStart', function () use ( $config )
        {
            Component::CheckParams( $config );
        } );
        $server->on( 'request', function ( SwRequest $request, SwResponse $response ) use ( $cors )
        {
            Coroutine::Init();
            Log::SetLogId();
            Response::Init( $response, $cors );
            Request::Init(
                is_array( $request->get ) ? $request->get : [],
                is_array( $request->post ) ? $request->post : [],
                is_array( $request->server ) ? $request->server : [],
                is_array( $request->files ) ? $request->files : [],
                is_array( $request->header ) ? $request->header : [],
                $request->rawContent()
            );
            Session::Reset();
            Cookie::Init( is_array( $request->cookie ) ? $request->cookie : [] );
            Router::Exec();
            Component::Release();
            Coroutine::Release();
        } );

        $server->start();
    }

}