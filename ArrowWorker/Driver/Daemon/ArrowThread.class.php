<?php
/**
 * User: Arrow
 * Date: 2017/02/03
 * Time: 20:28
 */

namespace ArrowWorker\Driver\Daemon;

/**
 * Class ArrowThread
 * @package ArrowWorker\Driver\Daemon
 */
class ArrowThread extends \Thread
{
    /**
     * 任务执行状态 - 执行中
     */
    const STATUS_RUNNING  = 1;

    /**
     * 任务执行状态 - 执行完成
     */
    const STATUS_FINISHED = 2;

    /**
     * 任务执行状态 - 等待中
     */
    const STATUS_WAITING  = 0;

    /**
     * 线程名称
     * @var string
     */
    public $threadName;

    /**
     * 线程任务组
     * @var Array
     */
    public $taskArray = [];

    /**
     * 当前是否有任务
     * @var Array
     */
    public $hasTask   = true;

    /**
     * 当前任务执行状态
     * @var Array
     */
    private $taskStat;

    /**
     * 任务执行次数统计
     * @var int
     */
    public $taskCount;

    /**
     * 线程是否继续执行任务
     * @var bool
     */
    private $isRuning  = true;

    /**
     * ArrowThread constructor.
     * @param string $threadName
     * @param array $task
     */
    public function __construct(string $threadName, array $task)
    {
        $this -> threadName = $threadName;
        $this -> taskStat   = self::STATUS_WAITING;
        $this -> taskArray = $task;
        $this -> taskCount = 0;
    }

    /**
     * run 执行任务
     * @author Louis
     */
    public function run()
    {
        while( $this -> isRuning )
        {
            if( $this -> hasTask )
            {
                $this -> taskStat = self::STATUS_RUNNING;
                if( isset( $this -> taskArray['argv'] ) )
                {
                    call_user_func_array( (array)$this -> taskArray['function'], (array)$this -> taskArray['argv'] );
                }
                else
                {
                    call_user_func($this -> taskArray['function']);
                }
                $this -> taskCount++;
                $this -> taskStat = self::STATUS_FINISHED;
            }
            else
            {
                $this -> taskStat = self::STATUS_WAITING;
                usleep(5);
            }
        }
        echo $this -> threadName .' ended!'.PHP_EOL;
    }


    /**
     * PushTask  压入任务（已废弃）
     * @author Louis
     * @param array $task
     */
    public function PushTask( array $task )
    {
        if( empty( $task ) )
        {
            $this -> taskArray  = $task;
        }
        $this -> hasTask = true;
    }

    /**
     * EndThread 结束线程任务（已废弃）
     * @author Louis
     */
    public function EndThread()
    {
        $this -> isRuning = false;
    }

    /**
     * ThreadStatus 任务执行状态
     * @author Louis
     */
    public function ThreadStatus()
    {
        return $this -> taskStat;
    }

}


