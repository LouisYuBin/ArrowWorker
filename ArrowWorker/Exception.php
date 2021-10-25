<?php
/**
 * User: louis
 * Time: 2017-10-10 11:19
 */

namespace ArrowWorker;

use ArrowWorker\Log\Log;

/**
 * Class Exception
 * @package ArrowWorker
 */
class Exception
{

    /**
     * init : set handle-function of error/exception
     */
    public static function init():void
    {
        set_error_handler([
            __CLASS__,
            'error',
        ]);
        set_exception_handler([
            __CLASS__,
            'exception',
        ]);
    }


    /**
     * error : error-handle function
     * @param int $code
     * @param string $msg
     * @param string $file
     * @param int $line
     * @param array $parameters
     */
    public static function error(int $code,  string $msg, string $file, int $line, $parameters=[]):void
    {
        Log::Dump("code: {$code}, message: {$msg}, file:{$file} ,line: {$line}, parameters : " . json_encode($parameters) . ", backtrace : " . json_encode(debug_backtrace()), Log::TYPE_ERROR, __METHOD__);
        sleep(1);
        exit(0);
    }


    /**
     * exception : exception function
     * @param object $exception
     * @return false
     */
    public static function exception(object $exception):bool
    {
        $exception = (array)$exception;
        $msg = '';
        $file = '';
        $line = 0;
        $code = 0;
        $backtrace = '';
        $elementNum = 0;
        foreach ($exception as $key => $val) {
            switch ($elementNum) {
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
                    $backtrace = json_encode($val);
            }
            $elementNum++;
        }
        Log::Dump("code: {$code}, message: {$msg}, file:{$file} ,line: {$line}, backtrace : {$backtrace}", Log::TYPE_EXCEPTION, __METHOD__);
        return false;
    }

}