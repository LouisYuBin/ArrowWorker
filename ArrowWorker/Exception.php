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

	const MODULE_NAME = 'Exception';
    /**
     * init : set handle-function of error/exception
     */
    static function Init()
    {
        set_error_handler( [
            __CLASS__,
            'Error',
        ] );
        set_exception_handler( [
            __CLASS__,
            'Exception',
        ] );
    }


    /**
     * error : error-handle function
     * @param int    $code
     * @param string $msg
     * @param string $file
     * @param int    $line
     * @param array  $parameters
     * @return false
     */
    public static function Error( int $code, string $msg, string $file, int $line, array $parameters)
    {
        Log::Dump( "code: {$code}, message: {$msg}, file:{$file} ,line: {$line}, parameters : ".json_encode($parameters).", backtrace : ".json_encode(debug_backtrace()), Log::TYPE_ERROR, self::MODULE_NAME );
        return false;
    }


    /**
     * exception : exception function
     * @param array $exception
     * @return false
     */
    public static function Exception( object $exception )
    {
        $exception = (array)$exception;
        $msg        = '';
        $file       = '';
        $line       = 0;
        $code       = 0;
        $backtrace  = '';
        $elementNum = 0;
        foreach ( $exception as $key => $val )
        {
            switch ( $elementNum )
            {
                case 0:
                    $msg = $val;
                    break;
                case 3:
                    $file = $val;
                    break;
                case 4:
                    $line = $val;
                    break;
                case 2:
                    $code = $val;
                    break;
                case 5:
                    $backtrace = json_encode( $val );
            }
            $elementNum++;
        }
        Log::Dump( "code: {$code}, message: {$msg}, file:{$file} ,line: {$line}, backtrace : {$backtrace}", Log::TYPE_EXCEPTION,self::MODULE_NAME );
        return false;
    }

}