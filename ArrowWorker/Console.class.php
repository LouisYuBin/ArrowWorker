<?php
/**
 * User: louis
 * Time: 18-5-9 下午11:14
 */

namespace ArrowWorker;


class Console
{
    private static $defaultProcessApp = 'app';

    public static function checkEnv()
    {
        if(php_sapi_name() != 'cli')
        {
            throw new \Exception("您当前模式为命令行模式，请在命令行执行相关命令，如：php index.php -c index -m index");
        }
    }

    private static function GetConfig() : array
    {
        $input = getopt('a:');
        $app   = static::$defaultProcessApp;
        if( isset($input['a']) )
        {
            $app = $input['a'];
        }

        //verify whether the daemon is configured
        $config = Config::App('Daemon');
        if( false===$config )
        {
            throw new \Exception("daemon not configured");
        }

        //check whether the specified deamon app configuration exists
        if( !isset($config[$app]) )
        {
            throw new \Exception("configuration for {$app}  does not exists");
        }

        $appConfig = $config[$app];

        //verify if the processor configuration is correct
        if( !isset($appConfig['processor']) || !is_array($appConfig['processor']) || count($appConfig['processor'])==0 )
        {
            throw new \Exception("daemon processor configuration is not correct");
        }

        return [$app, $appConfig];
    }

    public static function StartProcessor()
    {
        //verify if the application is started from command line
        static::checkEnv();

        //get app and configuration
        list($app, $config) = static::GetConfig();

        $daemon = Driver::Daemon( $app );
        foreach ($config['processor'] as $item)
        {
            if( !isset($item['function']) || !is_array($item['function']) || count($item['function'])<2 )
            {
                throw new \Exception("some processor configuration is not correct");
            }
            $item['function'] = [ new $item['function'][0], $item['function'][1] ];
            $daemon->AddTask( $item );
        }

        $daemon->Start();
    }
}