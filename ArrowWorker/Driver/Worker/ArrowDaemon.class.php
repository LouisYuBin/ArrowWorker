<?php
/**
 * User: Arrow
 * Date: 2016/8/1
 * Time: 19:52
 * Modified by louis at 2017/02/03 23:58
 */

namespace ArrowWorker\Driver\Worker;

use ArrowWorker\Driver\Worker AS worker;
use ArrowWorker\Log;


/**
 * Class ArrowDaemon
 * @package ArrowWorker\Driver\Daemon
 */
class ArrowDaemon extends Worker
{

    const LOG_PREFIX = 'worker : ';

    /**
     *进程生命周期
     */
    const LIFE_CYCLE = 300;

    /**
     * 单个任务默认并进程/线程数
     */
    const PROC_QUANTITY = 3;

    /**
     * 默认工作进程名
     */
    const processName = 'untitled';

    /**
     * 应用名称
     * @var string
     */
    private static $App_Name = 'ArrowWorker_worker';

    /**
     * 是否退出 标识
     * @var bool
     */
    private static $terminate = false;

    /**
     * 任务数量
     * @var int
     */
    private static $jobNum    = 0;

    /**
     * 任务map
     * @var bool
     */
    private static $jobs      = [];

    /**
     * 任务进程 ID map(不带管道消费的进程)
     * @var Array
     */
    private static $pidMap    = [];

	/**
	 * 最后队列消费map
	 * @var bool
	 */
	private static $consumePidMap = [];

    /**
     * 进程内任务执行状态 开始时间、运行次数、结束时间
     * @var Array
     */
    private static $workerStat  = ['start' => null, 'count' => 0, 'end' => null];


    /**
     * ArrowDaemon constructor.
     * @param array $config
     */
    public function __construct($config)
    {
        parent::__construct($config);
    }

    /**
     * init 单例模式初始化类
     * @author Louis
     * @param $config
     * @return ArrowDaemon
     */
    static function Init($config) : self
    {
        if(!self::$daemonObj)
        {
            self::$daemonObj = new self($config);
        }
        return self::$daemonObj;
    }

    /**
     * _setSignalHandler 进程信号处理设置
     * @author Louis
     * @param string $type 设置信号类型（子进程/监控进程）
     * @param int $lifecycle 闹钟周期
     */
    private function _setSignalHandler(string $type = 'parentsQuit')
    {
        switch($type)
        {
            case 'workerHandler':
                pcntl_signal(SIGCHLD, SIG_IGN,false);
                pcntl_signal(SIGTERM, SIG_IGN,false);
                pcntl_signal(SIGINT,  SIG_IGN,false);
                pcntl_signal(SIGQUIT, SIG_IGN,false);

                pcntl_signal(SIGALRM, array(__CLASS__, "signalHandler"),false);
                pcntl_signal(SIGUSR1, array(__CLASS__, "signalHandler"),false);
                break;
            default:
                pcntl_signal(SIGCHLD, array(__CLASS__, "signalHandler"),false);
                pcntl_signal(SIGTERM, array(__CLASS__, "signalHandler"),false);
                pcntl_signal(SIGINT,  array(__CLASS__, "signalHandler"),false);
                pcntl_signal(SIGQUIT, array(__CLASS__, "signalHandler"),false);
        }
    }

    /**
     * _setProcessAlarm
     * @param int $lifecycle
     */
    private function _setProcessAlarm(int $lifecycle)
    {
        $lifecycle = mt_rand(30, $lifecycle);
        pcntl_alarm($lifecycle);
    }


    /**
     * signalHandler 进程信号处理
     * @author Louis
     * @param int $signal
     * @return bool
     */
    public function signalHandler(int $signal)
    {
        switch($signal)
        {
            case SIGUSR1:
            case SIGALRM:
                self::$terminate = true;
                break;
            case SIGTERM:
            case SIGHUP:
            case SIGINT:
            case SIGQUIT:
                self::$terminate = true;
                break;
            default:
                return false;
        }

    }

    /**
     * _setProcessName  进程名称设置
     * @author Louis
     * @param string $proName
     */
    private function _setProcessName(string $proName)
    {
        if( PHP_OS=='Darwin')
        {
            return ;
        }
        $proName = self::$App_Name.'_'.$proName;
        if(function_exists('cli_set_process_title'))
        {
            @cli_set_process_title($proName);
        }
        elseif(extension_loaded('proctitle')&&function_exists('setproctitle'))
        {
            @setproctitle($proName);
        }
    }


    /**
     * start 挂载信号处理、生成任务worker、开始worker监控
     * @author Louis
     */
    public function Start()
    {

        self::$jobNum = count(self::$jobs,0);

        if( self::$jobNum == 0 )
        {
            Log::Dump(static::LOG_PREFIX."please add one task at least.");
            $this -> _finishMonitorExit()();
        }
        $this -> _setSignalHandler('monitorHandler');
        $this -> _forkWorkers();
        $this -> _startMonitor();
    }

    /**
     * _exitWorkers 循环退出所有worker
     * @author Louis
     */
    private function _exitWorkers()
    {
        foreach ( static::$pidMap as $pid => $groupId)
		{
			if( !posix_kill($pid,SIGUSR1) )
			{
				posix_kill($pid,SIGUSR1);
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
        while (1)
        {
            if(self::$terminate)
            {
            	//给工作进程发送退出信号
				$this->_exitWorkers();
				//等待进程退出
            	$this->_waitUnexitedProcess();
            	//开启管道读取进程并等待其退出
                $this->_finishChannelRead();
				//退出监控进程相关操作
				$this->_finishMonitorExit()();
            }

            pcntl_signal_dispatch();

            $status = 0;
            //returns the process ID of the child which exited, -1 on error or zero if WNOHANG was provided as an option (on wait3-available systems) and no child was available
            $pid    = pcntl_wait($status, WUNTRACED);
            pcntl_signal_dispatch();
            $this -> _handleExited( $pid, $status, false );

        }
    }

    /**
     * _waitUnexitedProcess 等待未退出的进程退出
     * @author Louis
     */
    private function _waitUnexitedProcess()
	{
		//统计未退出进程数
		$unExitedCount = count(static::$pidMap);

		//等待未退出进程退出
		for ($i=0; $i<$unExitedCount; $i++)
		{
			$status = 0;
			$pid    = pcntl_wait($status, WUNTRACED);
			$this -> _handleExited( $pid, $status );
		}
	}

    /**
     * _finishChannelRead 开启管道读取进程并等待其退出
     * @author Louis
     */
	private function _finishChannelRead()
    {
        //开启最终队列消费进程
        $this -> _startChannelFinishProcess();

        //等待未退出进程退出
        $consumeProcessNum = count(static::$consumePidMap);
        for ($i=0; $i<$consumeProcessNum; $i++)
        {
            $status  = 0;
            $pid     = pcntl_wait($status, WUNTRACED);
            $groupId = static::$consumePidMap[$pid];
            Log::Dump(static::LOG_PREFIX."channel-finish process : ".self::$jobs[ $groupId ]["processName"]."(".$pid.") exited at status : ".$status);
        }
        Log::Dump(static::LOG_PREFIX."channel-finish processes are all exited.");
    }


    /**
     * _handleExited 处理退出的进程
     * @author Louis
     * @param int $pid
     * @param int $status
     * @param bool $isExit
     */
    private function _handleExited(int $pid, int $status, bool $isExit=true)
    {
        if ($pid < 0)
        {
        	return;
		}

		$processGroupId = self::$pidMap[$pid];
		unset(self::$pidMap[$pid]);
		//组进程数处理
		self::$jobs[$processGroupId]['pidCount']--;
		
		//监控进程收到退出信号时则无需开启新的worker
		if( !$isExit )
		{
			$this -> _forkOneWorker($processGroupId);
		}
		Log::Dump(static::LOG_PREFIX."process : ".self::$jobs[$processGroupId]["processName"]."(".$pid.") exited at status : ".$status);
    }


    /**
     * _forkWorkers 给多有任务开启对应任务执行worker组
     * @author Louis
     */
    private function _forkWorkers()
    {
        for($i = 0; $i<self::$jobNum; $i++)
        {   
            while(self::$jobs[$i]['pidCount'] < self::$jobs[$i]['procQuantity'])
            {
                $this -> _forkOneWorker($i);
            }
            usleep(10000);
        }
    }


    /**
     * _forkOneWork 生成一个任务worker
     * @author Louis
     * @param int $taskGroupId
     */
    private function _forkOneWorker(int $taskGroupId)
    {
        $pid = pcntl_fork();
               
        if($pid > 0)
        {   
            self::$jobs[$taskGroupId]['pidCount']++;
            self::$pidMap[$pid] = $taskGroupId;
        }
        elseif($pid==0)
        {   
            $this -> _runWorker($taskGroupId, static::LIFE_CYCLE);
        }
        else
        {   
            sleep(1);
        }
    }


    /**
     * _runWorker 常驻执行任务
     * @author Louis
     * @param int $index
     * @param int $lifecycle
     */
    private function _runWorker(int $index, int $lifecycle)
    {
        $this -> _setSignalHandler('workerHandler');
        $this -> _setProcessAlarm($lifecycle);
        $this -> _setProcessName( self::$jobs[$index]['processName'] );
        $this -> _processRunTask( $index );
    }


    /**
     * _processRunTask 进程形式执行任务
     * @author Louis
     * @param int $index
     */
    private function _processRunTask(int $index)
    {
        self::$workerStat['start'] = time();
        Log::Dump(static::LOG_PREFIX.'process : '.self::$jobs[$index]['processName'].' started.');
        while( 1 )
        {
            if( self::$terminate )
            {
                self::$workerStat['end'] = time();
                $proWorkerTimeSum  = self::$workerStat['end'] - self::$workerStat['start'];
                Log::Dump(static::LOG_PREFIX.'process : '.self::$jobs[$index]['processName'].' finished '.self::$workerStat['count'].' times of its work in '.$proWorkerTimeSum.' seconds.' );
                exit(0);
            }
            pcntl_signal_dispatch();
            if( isset( self::$jobs[$index]['argv'] ) )
            {
                call_user_func_array( self::$jobs[$index]['function'], self::$jobs[$index]['argv'] );
            }
            else
            {
                call_user_func( self::$jobs[$index]['function'] );
            }
            pcntl_signal_dispatch();
            self::$workerStat['count']++;
        }
    }

	/**
	 * _startChannelFinishProcess 开启最终队列消费进程组
	 */
	private function _startChannelFinishProcess()
    {
        Log::Dump(static::LOG_PREFIX."starting channel-finish Process");

        for($i = 0; $i<self::$jobNum; $i++)
        {
            if( !self::$jobs[$i]['isChanReadProc'] )
            {
            	continue;
			}

			while(self::$jobs[$i]['pidCount'] < self::$jobs[$i]['procQuantity'])
			{
				$pid = pcntl_fork();

				if($pid > 0)
				{
					static::$consumePidMap[$pid] = $i;
                    self::$jobs[$i]['pidCount']++;
                }
				elseif($pid==0)
				{
					$this -> _consumeChannelTask($i);
				}
			}
            usleep(10000);
        }
        Log::Dump(static::LOG_PREFIX."channel-finish Processes are all started.");
    }

    /**
     * _processRunTask 进程形式执行任务
     * @author Louis
     * @param int $index
     */
    private function _consumeChannelTask(int $index)
    {
        $this -> _setProcessName( self::$jobs[$index]['processName'] );
        self::$workerStat['start'] = time();
        Log::Dump(static::LOG_PREFIX.'channel-finish '. self::$jobs[$index]['processName'].' starting work');
        $retryTimes = 0;
        while( 1 )
        {
            $channelStatus = true;
            if( isset( self::$jobs[$index]['argv'] ) )
            {
                $channelStatus = call_user_func_array( self::$jobs[$index]['function'], self::$jobs[$index]['argv'] );
            }
            else
            {
                $channelStatus = call_user_func( self::$jobs[$index]['function'] );
            }
            self::$workerStat['count']++;

            if( !$channelStatus )
            {
                //写入进程未退出
                foreach (static::$consumePidMap as $pid => $groupId)
                {
                    if( posix_kill($pid,SIGUSR2) )
                    {
                        $channelStatus = true;
                    }
                }

                //写入进程退出了，重试测试+1
                if ( !$channelStatus )
                {
                    $retryTimes++;
                }

                //未重试
                if ( $retryTimes > 1 )
                {
                    $channelStatus = true;
                }

                if ( !$channelStatus )
                {
                    break;
                }

            }

        }
        self::$workerStat['end'] = time();
        $proWorkerTimeSum  = self::$workerStat['end'] - self::$workerStat['start'];
        Log::Dump(static::LOG_PREFIX.'channel-finish '. self::$jobs[$index]['processName'].' finished '.self::$workerStat['count'].' times of its work in '.$proWorkerTimeSum.' seconds.' );
        exit(0);
    }

    /**
     * _finishMonitorExit() 删除进程pid文件、记录退出信息后正常退出粗
     * @author Louis
     */
    private function _finishMonitorExit()
    {
        Log::Dump(static::LOG_PREFIX."worker monitor exits.");
        exit(0);
    }

    /**
     * addTask 添加任务及相关属性
     * @author Louis
     * @param array $job
     */
    public function AddTask( $job = [] )
    {
        
        if(!isset($job['function'])||empty($job['function']))
        {
            Log::Dump(static::LOG_PREFIX." one Task at least is needed.");
            exit(0);
        }

        $job['pidCount']     = 0;
        $job['procQuantity'] = (isset($job['procQuantity']) && (int)$job['procQuantity']>0) ? $job['procQuantity'] : static::procQuantity ;
        $job['processName']  = (isset($job['procName'])     && !empty($job['procName']))    ? $job['procName']    : static::processName;
        $job['isChanReadProc'] = isset($job['isChanReadProc']) ? true : false;
        self::$jobs[] = $job;
    }

}

