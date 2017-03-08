<?php

namespace ArrowWorker\Driver\Daemon;
use ArrowWorker\Driver\Daemon\GeneratorTask;

class GeneratorScheduler
{
    //调度器列表
    protected $taskMap = [];
    //是否退出
    protected $isExit  = false;
    //任务执行计数
    protected static $execCount = 0;
 
    public function __construct( $proName )
    {
        //Todo
    }
  
    //添加任务
    public function newTask( Generator $coroutine )
    {
         $task =  new GeneratorTask( $coroutine );
         $this -> taskMap[] = $task;
    }
  
    //循环执行任务
    public function run()
    {
        while( 1 )
        {
            if ( $this -> isExit )
            {
                break;
            }

            foreach( $this -> taskMap as $taskId => $task )
            {
                $return = $task -> run();
                //计数
                self::$execCount += $return;
  
                if ( $task->isFinished() )
                {
                    unset( $this->taskMap[$taskId] );
                }
            }
        }
    }

    public function taskCount()
    {
        return self::$execCount;
    }

}

