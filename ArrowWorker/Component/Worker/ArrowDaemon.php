<?php

namespace ArrowWorker\Component\Worker;

use ArrowWorker\App;
use ArrowWorker\Log;
use ArrowWorker\Component;
use ArrowWorker\Daemon;

use ArrowWorker\Component\Worker;

use ArrowWorker\Library\Coroutine;
use ArrowWorker\Library\Process;

class ArrowDaemon extends Worker
{

    /**
     *
     */
    const MODULE_NAME = 'Worker';

    /**
     * process life time
     */
    const LIFE_CYCLE = 60;

    /**
     * concurrence coroutine
     */
    const COROUTINE_QUANTITY = 3;

    /**
     * default process name
     */
    const PROCESS_NAME = 'unnamed';

    /**
     * 是否退出 标识
     * @var bool
     */
    private $_terminate = false;

    /**
     * 任务数量
     * @var int
     */
    private $_jobNum = 0;

    /**
     * 任务map
     * @var array
     */
    private  $_jobs = [];

    /**
     * 任务进程 ID map(不带管道消费的进程)
     * @var array
     */
    private $_pidMap = [];

    private $_execCount = 0;

    /**
     * @var Component;
     */
    private $_component;


    /**
     * ArrowDaemon constructor.
     *
     * @param array $config
     */
    public function __construct( $config )
    {
        parent::__construct($config);
    }

    /**
     * init 单例模式初始化类
     * @author Louis
     *
     * @param $config
     *
     * @return ArrowDaemon
     */
    public static function Init( $config ) : self
    {
        self::$_user = $config['user']  ?? 'root';
        self::$_group = $config['group'] ?? 'root';

        if ( !self::$daemonObj )
        {
            self::$daemonObj = new self($config);
        }

        return self::$daemonObj;
    }

    /**
     * _setSignalHandler 进程信号处理设置
     * @author Louis
     *
     * @param string $type 设置信号类型（子进程/监控进程）
     */
    private function _setSignalHandler( string $type = 'parentsQuit' )
    {
        // SIGTSTP have to be ignored on mac os
        switch ( $type )
        {
            case 'workerHandler':
                pcntl_signal(SIGCHLD, SIG_IGN, false);
                pcntl_signal(SIGTERM, SIG_IGN, false);
                pcntl_signal(SIGINT, SIG_IGN, false);
                pcntl_signal(SIGQUIT, SIG_IGN, false);
                pcntl_signal(SIGTSTP, SIG_IGN, false);


                pcntl_signal(SIGALRM, [
                    __CLASS__, "signalHandler",
                ], false);
                pcntl_signal(SIGUSR1, [
                    __CLASS__, "signalHandler",
                ], false);
                break;

            case 'chanHandler':
                pcntl_signal(SIGCHLD, SIG_IGN, false);
                pcntl_signal(SIGTERM, SIG_IGN, false);
                pcntl_signal(SIGINT, SIG_IGN, false);
                pcntl_signal(SIGQUIT, SIG_IGN, false);
                pcntl_signal(SIGUSR1, SIG_IGN, false);
                pcntl_signal(SIGTSTP, SIG_IGN, false);


                pcntl_signal(SIGUSR2, [
                    __CLASS__, "signalHandler",
                ], false);

                break;
            default:
                pcntl_signal(SIGCHLD, [
                    __CLASS__, "signalHandler",
                ], false);
                pcntl_signal(SIGTERM, [
                    __CLASS__, "signalHandler",
                ], false);
                pcntl_signal(SIGINT, [
                    __CLASS__, "signalHandler",
                ], false);
                pcntl_signal(SIGQUIT, [
                    __CLASS__, "signalHandler",
                ], false);
                pcntl_signal(SIGUSR2, [
                    __CLASS__, "signalHandler",
                ], false);
                pcntl_signal(SIGTSTP, SIG_IGN, false);

        }
    }


    /**
     * signalHandler 进程信号处理
     * @author Louis
     *
     * @param int $signal
     *
     * @return void
     */
    public function signalHandler( int $signal )
    {
        //Log::Dump(static::MODULE_NAME . "got a signal {$signal} : " . Process::SignalName($signal));
        switch ( $signal )
        {
            case SIGUSR1:
            case SIGALRM:
                $this->_terminate = true;
                break;
            case SIGTERM:
            case SIGHUP:
            case SIGINT:
            case SIGQUIT:
                $this->_terminate = true;
                break;
            case SIGUSR2:
                //剩余队列消费专用
                $this->_terminate = true;
                break;
            default:
                return;
        }

    }


    /**
     * _setProcessName  进程名称设置
     * @author Louis
     *
     * @param string $proName
     */
    private function _setProcessName( string $proName )
    {
        Process::SetName(Daemon::$identity . '_Worker_' . $proName);
    }

    /**
     * @param int $processGroupId
     * @param int $lifecycle
     */
    private function _setAlarm(int $processGroupId, int $lifecycle )
    {
        Process::SetAlarm(mt_rand(($processGroupId+1)*$lifecycle, ($processGroupId+2)*$lifecycle));
    }


    /**
     * start 挂载信号处理、生成任务worker、开始worker监控
     * @author Louis
     */
    public function Start()
    {

        $this->_jobNum = count($this->_jobs, 0);

        if ( $this->_jobNum == 0 )
        {
            Log::Dump('please add one task at least.', Log::TYPE_WARNING, self::MODULE_NAME,);
            $this->_exitMonitor();
        }
        $this->_setSignalHandler('monitorHandler');
        $this->_forkWorkers();
        $this->_startMonitor();
    }

    /**
     * _exitWorkers 退出当前进程组中最前面的进程
     * @param int $headGroupId
     */
    private function _exitWorkers(int $headGroupId)
    {
        foreach ( $this->_pidMap as $pid => $groupId )
        {
            if ( $groupId != $headGroupId )
            {
                continue;
            }

            if ( !Process::Kill($pid, SIGUSR1) )
            {
                Process::Kill($pid, SIGUSR1);
            }
            usleep(10000);
        }
    }


    /**
     * _exitWorkers 开启worker监控
     * @author Louis
     */
    private function _startMonitor()
    {
        while ( 1 )
        {
            if ( $this->_terminate )
            {
                $toBeExitedGroupId = $this->_calcToBeExitedGroup();
                //给工作进程发送退出信号
                $this->_exitWorkers($toBeExitedGroupId);
                //等待进程退出
                $this->_waitToBeExitedProcess($toBeExitedGroupId);

                if( 0==count($this->_pidMap) )
                {
                    //退出监控进程相关操作
                    $this->_exitMonitor();
                }
                else
                {
                    continue ;
                }

            }

            pcntl_signal_dispatch();

            $status = 0;
            //returns the process ID of the child which exited, -1 on error or zero if WNOHANG was provided as an option (on wait3-available systems) and no child was available
            $pid = Process::Wait($status);
            pcntl_signal_dispatch();
            $this->_handleExited($pid, $status, false);

        }
    }

    /**
     * @return int
     */
    private function _calcToBeExitedGroup() : int
    {
        $groups = array_unique(array_values($this->_pidMap));
        if( 0==count($groups) )
        {
            return 0;
        }
        sort($groups);
        return (int)$groups[0];
    }


    /**
     * @param  int $groupId
     */
    private function _waitToBeExitedProcess(int $groupId)
    {
        $leftProcessCount = 0;
        foreach ($this->_pidMap as $pid=>$gid)
        {
            if( $gid==$groupId )
            {
                $leftProcessCount++;
            }
        }

        for($i=0; $i<$leftProcessCount; $i++)
        {
            $status = 0;
            RE_WAIT:
            $pid = Process::Wait($status, WUNTRACED);
            if ( $pid == -1 )
            {
                goto RE_WAIT;
            }

            $this->_handleExited($pid, $status, $this->_pidMap[$pid]==$groupId ? true : false);
        }
    }



    /**
     * _handleExited 处理退出的进程
     * @author Louis
     *
     * @param int  $pid
     * @param int  $status
     * @param bool $isExit
     */
    private function _handleExited( int $pid, int $status, bool $isExit = true )
    {
        if ( $pid < 0 )
        {
            return;
        }

        $taskId = $this->_pidMap[ $pid ];
        unset($this->_pidMap[ $pid ]);

        Log::Dump( "{$this->_jobs[ $taskId ]["processName"]}({$pid}) exited at status {$status}", Log::TYPE_DEBUG, self::MODULE_NAME);
        usleep(0==$status ? 10 : 10000 );

        //监控进程收到退出信号时则无需开启新的worker
        if ( !$isExit )
        {
            $this->_forkOneWorker($taskId);
        }

    }


    /**
     * _forkWorkers 给多有任务开启对应任务执行worker组
     * @author Louis
     */
    private function _forkWorkers()
    {
        for ( $i = 0; $i < $this->_jobNum; $i++ )
        {
            for($j=0; $j<$this->_jobs[$i]['processQuantity']; $j++)
            {
                $this->_forkOneWorker($i);
            }
        }
    }


    /**
     * _forkOneWork 生成一个任务worker
     * @author Louis
     *
     * @param int $taskId
     */
    private function _forkOneWorker( int $taskId )
    {
        $pid = Process::Fork();

        if ( $pid > 0 )
        {
            $this->_pidMap[ $pid ] = $taskId;
        }
        else if ( $pid == 0 )
        {
            $this->_runWorker($taskId, self::LIFE_CYCLE);
        }
        else
        {
            sleep(1);
        }
    }


    /**
     * _runWorker 常驻执行任务
     * @author Louis
     *
     * @param int $index
     * @param int $lifecycle
     */
    private function _runWorker( int $index, int $lifecycle )
    {
        Log::Dump('starting ' . $this->_jobs[ $index ]['processName'] . '(' . Process::Id() . ')', Log::TYPE_DEBUG, self::MODULE_NAME );
        $this->_setSignalHandler('workerHandler');
        $this->_setAlarm($index,$lifecycle);
        $this->_setProcessName($this->_jobs[ $index ]['processName']);
        $this->_initComponent();
        Process::SetExecGroupUser(self::$_group, self::$_user);
        Coroutine::enable();
        Coroutine::Create(function () use ( $index )
        {
            $this->_component->InitPool($this->_jobs[ $index ]['components']);
        });
        Coroutine::Wait();
        $this->_runProcessTask($index);
    }

    private function _initComponent()
    {
        $this->_component = Component::Init(App::TYPE_WORKER );
    }

    /**
     * _runProcessTask 进程形式执行任务
     * @author Louis
     *
     * @param int $index
     */
    private function _runProcessTask( int $index )
    {
        $timeStart = time();

        Log::Dump("{$this->_jobs[ $index ]['processName']} started.",Log::TYPE_DEBUG, self::MODULE_NAME);

        while ( $this->_jobs[ $index ]['coCount'] < $this->_jobs[ $index ]['coQuantity'] )
        {
            Coroutine::Create(function () use ( $index, $timeStart)
            {
                while ( true )
                {
                    Log::Init();
                    if ( isset($this->_jobs[ $index ]['argv']) )
                    {
                        $result = call_user_func_array($this->_jobs[ $index ]['function'], $this->_jobs[ $index ]['argv']);
                    }
                    else
                    {
                        $result = call_user_func($this->_jobs[ $index ]['function']);
                    }
                    $this->_execCount++;

                    //release components resource after finish one work
                    $this->_component->Release();

                    if ( $this->_terminate && false==(bool)$result )
                    {
                    	break;
                    }
                }

            });
            $this->_jobs[ $index ]['coCount']++;
        }
        
        Coroutine::Create(function (){
        	while (true)
	        {
		        if ( $this->_terminate )
		        {
			        break;
		        }
		        Coroutine::Sleep(0.2);
		        pcntl_signal_dispatch();
	        }
        });

        Coroutine::Wait();
        $execTimeSpan = time() - $timeStart;
        Log::Dump("{$this->_jobs[ $index ]['processName']} finished {$this->_execCount} times / {$execTimeSpan} S.", Log::TYPE_DEBUG, self::MODULE_NAME );
        exit(0);

    }

    /**
     * _exitMonitor 删除进程pid文件、记录退出信息后正常退出粗
     * @author Louis
     */
    private function _exitMonitor()
    {
        Log::Dump( "exited", Log::TYPE_DEBUG, self::MODULE_NAME);
        exit(0);
    }

    /**
     * addTask 添加任务及相关属性
     * @author Louis
     *
     * @param array $job
     */
    public function AddTask( $job = [] )
    {

        if ( !isset($job['function']) || empty($job['function']) )
        {
            Log::DumpExit("one Task at least is needed ", Log::TYPE_ERROR, self::MODULE_NAME);
        }

        $job['coCount']    = 0;
        $job['coQuantity'] = ( isset($job['coQuantity']) && (int)$job['coQuantity'] > 0 ) ? (int)$job['coQuantity'] :
            self::COROUTINE_QUANTITY;
        $job['processQuantity'] = ( isset($job['processQuantity']) && (int)$job['processQuantity'] > 0 ) ? (int)$job['processQuantity'] :
            1;
        $job['processName'] = ( isset($job['name']) && !empty($job['name']) ) ? $job['name'] : self::PROCESS_NAME;
        $job['components'] = isset($job['components']) && is_array($job['components']) ? $job['components'] : [];

        $this->_jobs[] = $job;
    }

}

