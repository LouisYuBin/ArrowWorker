<?php

namespace ArrowWorker;

use ArrowWorker\Library\Process;
use ArrowWorker\Library\System\LoadAverage;

class Console
{

    /**
     * @var Console
     */
    private static $_instance;

    private $_argv = [];

    private $_entryFile = '';

    private $_application = '';

    private $_action = '';

    private $_env = '';

    private $_isDebug = false;


    private function __construct()
    {
        $this->_checkStartEnv();
        $this->_parseArgv();
        $this->_checkExtension();

        Config::Init( $this->GetEnv() );
    }

    private function _stop()
    {
        $pid = Daemon::GetPid();
        if ( 0 === $pid )
        {
            Log::Hint( 'Arrow is not running.' );
            return false;
        }

        for ( $i = 1; $i > 0; $i++ )
        {
            if ( $i == 1 )
            {
                if ( Process::Kill( $pid, SIGTERM ) )
                {
                    echo( 'Arrow stopping' );
                }
                else
                {
                    Log::Hint( 'Arrow is not running.' );
                    return false;
                }
            }
            else
            {
                if ( !Process::Kill( $pid, SIGTERM, true ) )
                {
                    Log::Hint( 'stopped successfully.' );
                    return true;
                }
                else
                {
                    echo '.';
                    sleep( 1 );
                }
            }
        }
    }

    public static function Init()
    {
        if ( self::$_instance instanceof Console )
        {
            goto _RETURN;
        }

        self::$_instance = new self();

        _RETURN:
        return self::$_instance;
    }

    public function Execute() : void
    {
        switch ( $this->_action )
        {
            case 'stop':
                $this->_stop();
                break;
            case 'start':
                $this->_start();
                break;
            case 'status':
                $this->_getStatus();
                break;
            case 'restart':
                $this->_restart();
                break;
            default:
                Log::Hint( "Oops! Unknown operation. please use \"php {$this->_entryFile} start/stop/status/restart\" to start/stop/restart the service" );
        }
        return;
    }

    private function _start()
    {
        Log::Hint( "starting ...{$this->_application}({$this->_env})" );
        Daemon::Start( $this->_application, $this->_isDebug );
    }

    private function _getStatus()
    {
        $keyword = PHP_OS == 'Darwin' ? $this->_entryFile : Daemon::APP_NAME . '_' . Daemon::GetPid();
        $commend = "ps -e -o 'user,pid,ppid,pcpu,%mem,args' | grep {$keyword}";
        $output  = 'user | pid | ppid | cpu usage | memory usage | process name' . PHP_EOL;
        $results = LoadAverage::Exec( $commend );
        $output  .= implode( PHP_EOL, $results );
        echo $output . PHP_EOL;
    }

    private function _restart()
    {
        if ( $this->_stop() )
        {
            $this->_start();
        }
    }

    private function _parseArgv()
    {
        global $argv;
        $this->_argv = $argv;
        if ( count( $this->_argv ) < 2 )
        {
            Log::DumpExit( 'Parameter missed' );
        }

        [
            $this->_entryFile,
            $this->_action,
        ] = $argv;

        $this->_application = $argv[ 2 ] ?? 'server';
        $this->_env         = $argv[ 3 ] ?? 'Dev';
        $this->_isDebug  = isset( $argv[ 4 ] ) && 'true' === trim( $argv[ 4 ] ) ? true : false;
    }

    private function _checkStartEnv()
    {
        if ( php_sapi_name() != "cli" )
        {
            Log::DumpExit( "Arrow hint : only run in command line mode" );
        }
    }


    private function _checkExtension()
    {
        if ( !extension_loaded( 'swoole' ) )
        {
            Log::DumpExit( 'extension swoole is not installed/loaded.' );
        }

        if ( !extension_loaded( 'sysvmsg' ) )
        {
            Log::DumpExit( 'extension sysvmsg is not installed/loaded.' );
        }

        if ( (int)str_replace( '.', '', ( new \ReflectionExtension( 'swoole' ) )->getVersion() ) < 400 )
        {
            Log::DumpExit( 'swoole version must be newer than 4.0 .' );
        }

    }


    public function IsDebug()
    {
        return $this->_isDebug;
    }

    public function GetEnv()
    {
        return ucfirst($this->_env);
    }
}
