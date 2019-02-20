<?php
/**
 * User: louis
 * Time: 18-5-10 下午12:38
 */

namespace ArrowWorker;

use \Swoole\Coroutine as Co;
use \Swoole\Http\Server as Http;


class Swoole
{
    public static $Http = [
        'port'      => 8888,
        'workerNum' => 4,
        'backlog'   => 1000,
        'pipeBufferSize'   => 1024*1024*100,
        'socketBufferSize' => 1024*1024*100,
        'maxRequest'       => 20000,
        'reactorNum'       => 4,
        'maxContentLength' => 2088960,
        'maxCoroutine'     => 10000,
        'enableStaticHandler' => false,
        'documentRoot'     => ''
    ];

    private static function _getHttpConfig()
    {
        $config = Config::Get("Http");
        if( false===$config )
        {
            Log::Warning('swoole http configuration file does not exists, using default configuration');
        }

        static::$Http = array_merge(static::$Http, $config);
    }


    public static function Http()
    {
        static::_getHttpConfig();
        Router::Init();
        $server = new Http("0.0.0.0", static::$Http['port']);
        $server->set([
            'worker_num' => static::$Http['workerNum'],
            'daemonize'  => false,
            'backlog'    => static::$Http['backlog'],
            'package_max_length'   => static::$Http['maxContentLength'],
            'enable_static_handler' => static::$Http['enableStaticHandler'],
            'reactor_num'        => static::$Http['reactorNum'],
            'pipe_buffer_size'   => static::$Http['pipeBufferSize'],
            'socket_buffer_size' => static::$Http['socketBufferSize'],
            'max_request'        => static::$Http['maxRequest'],
            'max_coroutine'      => static::$Http['maxCoroutine'],
            'document_root'      => static::$Http['documentRoot'],
            'log_file' => Log::$StdoutFile
        ]);
        $server->on('Request', function($request, $response) {
            Cookie::Init(is_array($request->cookie) ? $request->cookie : [], $response);
            Request::Init(
                is_array($request->get)   ? $request->get : [],
                is_array($request->post) ? $request->post : [],
                is_array($request->server) ? $request->server : [],
                is_array($request->files) ? $request->files : []
            );
            Session::Reset();
            Response::Init($response);
            Router::Go();
        });

        $server->start();
    }

    /**
     * get swoole coroutine id
     * @return int
     */
    public static function GetCid() : int
    {
        return Co::getuid();
    }
}