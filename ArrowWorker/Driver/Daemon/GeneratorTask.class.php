<?php

namespace ArrowWorker\Driver\Daemon;

class GeneratorTask
{
    protected $coroutine;
    protected $sendValue = null;
    protected $beforeFirstYield = true;
 
    //初始化调度器 
    public function __construct( Generator $coroutine)
    {
        $this -> coroutine = $coroutine;
    }
  
    public function setSendValue($sendValue)
    {
        $this->sendValue = $sendValue;
    }
  
    public function run()
    {
        if ( $this->beforeFirstYield )
        {
             $this -> beforeFirstYield = false;
             return $this -> coroutine -> current();
        }
        else
        {
             $retval = $this -> coroutine -> send( $this->sendValue );
             $this -> sendValue = null;
             return $retval;
         }
     }
  
     public function isFinished()
     {
        return !$this -> coroutine -> valid();
     }

 }
