<?php
/**
 * User: louis
 * Time: 18-5-9 下午11:14
 */

namespace ArrowWorker;

use \ArrowWorker\Driver\Worker\ArrowDaemon;

class Worker
{
    private static $defaultProcessApp = 'app';


    private static function _getConfig() : array
    {
        //verify whether the daemon is configured
        $config = Config::Get('Worker');
        if( false===$config )
        {
            Log::DumpExit("worker configuration is not exists.");
        }

        //verify if the processor configuration is correct
        if( !isset($config['group']) || !is_array($config['group']) || count($config['group'])==0 )
        {
            Log::DumpExit("daemon processor configuration is not correct");
        }

        return $config;
    }

    public static function Start()
    {
        $config = static::_getConfig();
        $daemon = ArrowDaemon::Init($config);
        foreach ($config['group'] as $item)
        {
            if( !isset($item['function']) || !is_array($item['function']) || count($item['function'])<2 )
            {
                Log::DumpExit("some processor configuration is not correct");
            }
            $item['function'] = [ new $item['function'][0], $item['function'][1] ];
            $daemon->AddTask( $item );
        }

        $daemon->Start();
    }
}