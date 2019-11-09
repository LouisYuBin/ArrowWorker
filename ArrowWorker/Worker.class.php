<?php
/**
 * User: louis
 * Time: 18-5-9 下午11:14
 */

namespace ArrowWorker;

use \ArrowWorker\Driver\Worker\ArrowDaemon;

class Worker
{

    private static function _getConfig() : array
    {
        //verify whether the daemon is configured
        $config = Config::Get('Worker');
        if( false===$config )
        {
            Log::DumpExit("worker configuration is not exists.");
        }

        //verify if the processor configuration is correct
        if( !is_array($config) || !isset($config['worker']) || !is_array($config['worker']) || count($config['worker'])==0 )
        {
            Log::DumpExit("daemon processor configuration is not correct");
            usleep(1000000);
        }

        return $config;
    }

    public static function Start()
    {
        $config = static::_getConfig();
        $daemon = ArrowDaemon::Init($config);
        foreach ($config['worker'] as $item)
        {
            if( !isset($item['function']) || !isset($item['function'][0]) || !isset($item['function'][1]) )
            {
                Log::Dump("some processor configuration is not correct");
                continue ;
            }
            $class = (string)$item['function'][0];

            if( !class_exists($class) )
            {
                Log::Dump("{$item['function'][0]} does not exists.");
                continue;
            }

            $method   = (string)$item['function'][1];
            $instance = new $class;
            if( !method_exists( $instance, $method) )
            {
                Log::Dump("{$class}->{$method} does not exists.");
                continue;
            }
            $item['function'] = [ $instance, $method ];
            $daemon->AddTask( $item );
        }

        $daemon->Start();
    }
}