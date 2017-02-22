<?php
/**
 * User: Arrow
 * Date: 2017/02/03
 * Time: 20:28
 */

namespace ArrowWorker\Driver\Daemon;

class ArrowThread extends \Thread
{
    //执行中
    const STATUS_RUNNING  = 1;
    //执行完成
    const STATUS_FINISHED = 2;
    //等待中
    const STATUS_WAITING  = 0;
    //线程名称
    public $threadName;
    //任务数组
    public $taskArray = '';
    //是否有任务
    public $hasTask   = false;
    //当前任务执行状态
    private $taskStat;

    //是否执行
    private $isRuning  = true;

    public function __construct( $threadName )
    {
        $this -> threadName = $threadName;
        $this -> taskStat   = self::STATUS_WAITING;
    }

    //执行任务
    public function run()
    {
        while( $this -> isRuning )
        {

            if( $this -> hasTask )
            {

                $taskArray = json_decode( $this -> taskArray, true );
                $this -> taskStat = self::STATUS_RUNNING;
                if( isset( $taskArray['argv'] ) )
                {
                    call_user_func_array( $taskArray['function'], $taskArray['argv'] );
                }
                else
                {
                    call_user_func($taskArray['function']);
                }
                $this -> taskStat = self::STATUS_FINISHED;
                $this -> hasTask  = false;   
            }
            else
            {
                $this -> taskStat = self::STATUS_WAITING;
                usleep(5);
            }
        }
    }

    //分发任务
    public function pushTask( $task )
    {
        if( empty( $this -> taskArray ) )
        {
            $taskString = json_encode(['function' => $task['function'], 'argv' => $task['argv'] ]);
            $this -> taskArray  = $taskString;
        }
        $this -> hasTask = true;
    }

    //结束线程工作
    public function endThread()
    {
        $this -> isRuning = false;
    }

    public function threadStatus()
    {
        return $this -> taskStat;
    }

}


