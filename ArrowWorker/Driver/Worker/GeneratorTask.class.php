<?php
/*
 * generator task
 * By Louis at 2017-03-08
 */
namespace ArrowWorker\Driver\Worker;

class GeneratorTask
{
    protected $coroutine;
    protected $sendValue = null;
    protected $beforeFirstYield = true;

    public function __construct( \Generator $coroutine)
    {
        $this -> coroutine = $coroutine;
    }
   
    //set the value while will be sent to generator
    public function setSendValue($sendValue)
    {
        $this->sendValue = $sendValue;
    }
   
    // run generator and send information to generator
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

    //Check if the iterator has been closed 
    public function isFinished()
    {
        return !$this -> coroutine -> valid();
    }

}

