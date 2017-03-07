<?php

namespace ArrowWorker\Driver\Daemon;
use ArrowWorker\Driver\Daemon\GeneratorTask;

class GeneratorScheduler
{
    //调度器列表
    protected $taskMap = [];
    //是否退出
    protected $isExit  = false;
 
    public function __construct()
    {
        //Todo
    }
  
    //添加任务
    public function newTask( Generator $coroutine )
    {
         $task =  new GeneratorTask( $tid, $coroutine );
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
                $task -> run();
  
               if ( $task->isFinished() )
                {
                    unset( $this->taskMap[$taskId] );
                }
            }
        }
    }
}

