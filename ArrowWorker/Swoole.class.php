<?php
/**
 * User: louis
 * Time: 18-5-10 下午12:38
 */

namespace ArrowWorker;


class Swoole
{
    public static $Http = [
        'port'      => 8888,
        'workerNum' => 4,
        'backlog'   => 1000,
        'maxContentLength' => 2088960,
        'enableStaticHandler' => false,
        'documentRoot' => ''
    ];

    private static function getHttpConfig()
    {
        $config = Config::Get("Swoole");
        if( false===$config )
        {
            throw new \Exception('swoole config does not exists');
        }

        if( !isset($config['http']) )
        {
            throw new \Exception('swoole http config does not exists');
        }

        static::$Http = array_merge(static::$Http, $config['http']);
    }


    public static function Http()
    {
        static::getHttpConfig();
        $server = new \swoole_http_server("0.0.0.0", static::$Http['port']);
        $server->set([
            'worker_num' => static::$Http['workerNum'],
            'daemonize'  => false,
            'backlog'    => static::$Http['backlog'],
            'package_max_length' => static::$Http['maxContentLength'],
            'enable_static_handler' => static::$Http['enableStaticHandler'],
            'document_root' => static::$Http['documentRoot'],
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
            Router::Start();
        });

        $server->start();
        ob_end_flush();
    }
}