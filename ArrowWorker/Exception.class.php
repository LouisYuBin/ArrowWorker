<?php
/**
 * User: louis
 * Time: 2017-10-10 11:19
 */

namespace ArrowWorker;


/**
 * Class Exception
 * @package ArrowWorker
 */
class Exception
{
	/**
	 * @var int error/exception code
	 */
	private static $code  = 0;

	/**
	 * @var string error/exception message
	 */
    private static $msg   = '';

	/**
	 * @var int file which error/exception happend
	 */
    private static $file  = '';

	/**
	 * @var int exception/error line number
	 */
    private static $line  = 0;

	/**
	 * @var int exception file trace
	 */
    private static $trace = '';

	/**
	 * init : set handle-function of error/exception
	 */
	static function Init()
    {
        set_error_handler([__CLASS__ , 'error']);
        set_exception_handler([__CLASS__,'exception']);
    }


	/**
	 * error : error-handle function
	 * @param int $code
	 * @param string $msg
	 * @param string $file
	 * @param int $line
	 */
	static function error(int $code=0, string $msg='', string $file='', int $line=0 )
    {
        //ob_clean();
        if( APP_TYPE=='web' && APP_STATUS=='debug' )
        {
            header("HTTP/1.1 500 Something must be wrong with your program,by ArrowWorker!");
            exit("<b>Error:</b><br />Code : {$code}<br />File : {$file}<br />Line : {$line }<br />Message : {$msg}<br />");
        }
        else if( APP_TYPE=='web' && APP_STATUS!='debug' )
        {
            header("HTTP/1.1 500 Something must be wrong with your program,by ArrowWorker!");
            exit( json_encode( ['code' => 500, 'msg' => 'something is wrong with the server...'] ) );
        }
        else if( APP_TYPE=='cli')
        {
            static::_removePidFile();
            exit(PHP_EOL."Error:".PHP_EOL."File: {$file}".PHP_EOL."Line: {$line}".PHP_EOL."Message: {$msg}".PHP_EOL);
        }
    }

    /**
     * _removePidFile 删除pid文件
     * @author Louis
     */
    static function _removePidFile()
    {
        $daemonArray = Config::App('Daemon');
        foreach ($daemonArray as $daemonConfig)
        {
            $pidPath = '/var/run'.$daemonConfig['pid'].'.pid';
            if(file_exists($pidPath))
            {
                @unlink($pidPath);
            }
        }
    }


	/**
	 * exception : exception function
	 * @param string $msg
	 */
	static function exception(string $msg = '')
    {
        $exception = (array)$msg;
        $elementNum = 0;
        foreach ($exception as $key => $val)
        {
            switch ($elementNum)
            {
                case 0:
                    self::$msg = $val;
                    break;
                case 3:
                    self::$file = $val;
                    break;
                case 4:
                    self::$line = $val;
                    break;
                case 2:
                    self::$code = $val;
                    break;
                case 5:
                    self::$trace = json_encode($val);
            }
            $elementNum++;
        }
        self::error( self::$code, self::$msg, self::$file, self::$line );
    }

}