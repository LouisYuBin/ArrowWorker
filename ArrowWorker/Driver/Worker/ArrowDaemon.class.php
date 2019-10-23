<?php
/**
 * User: Arrow
 * Date: 2016/8/1
 * Time: 19:52
 * Modified by louis at 2019/06/13 22:33:58
 */

namespace ArrowWorker\Driver\Worker;

use Swoole\Coroutine as Co;
use \swoole\Runtime;

use ArrowWorker\Component;
use ArrowWorker\Daemon;
use ArrowWorker\Driver\Worker;
use ArrowWorker\Log;

use ArrowWorker\Lib\Coroutine;
use ArrowWorker\Lib\Process;



/**
 * Class ArrowDaemon
 * @package ArrowWorker\Driver\Daemon
 */
class ArrowDaemon extends Worker
{

    const LOG_PREFIX = '[ Worker  ] ';

    /**
     * process life time
     */
    const LIFE_CYCLE = 300;

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
    private static $terminate = false;

    /**
     * 任务数量
     * @var int
     */
    private static $jobNum = 0;

    /**
     * 任务map
     * @var array
     */
    private static $jobs = [];

    /**
     * 任务进程 ID map(不带管道消费的进程)
     * @var array
     */
    private static $pidMap = [];

    /**
     * 最后队列消费map
     * @var array
     */
    private static $consumePidMap = [];

    /**
     * 进程内任务执行次数
     * @var array
     */
    private static $execCount = 0;


    /**
     * ArrowDaemon constructor.
     * @param array $config
     */
    public function __construct( $config )
    {
        parent::__construct( $config );
    }

    /**
     * init 单例模式初始化类
     * @author Louis
     * @param $config
     * @return ArrowDaemon
     */
    public static function Init( $config ) : self
    {
        self::$_user  = $config['user']  ?? 'root';
        self::$_group = $config['group'] ?? 'root';

        if ( !self::$daemonObj )
        {
            self::$daemonObj = new self( $config );
        }
        return self::$daemonObj;
    }

    /**
     * _setSignalHandler 进程信号处理设置
     * @author Louis
     * @param string $type 设置信号类型（子进程/监控进程）
     */
    private function _setSignalHandler( string $type = 'parentsQuit' )
    {
        // SIGTSTP have to be ignored on mac os
        switch ( $type )
        {
            case 'workerHandler':
                pcntl_signal( SIGCHLD, SIG_IGN, false );
                pcntl_signal( SIGTERM, SIG_IGN, false );
                pcntl_signal( SIGINT, SIG_IGN, false );
                pcntl_signal( SIGQUIT, SIG_IGN, false );
                pcntl_signal( SIGTSTP, SIG_IGN, false );


                pcntl_signal( SIGALRM, [
                    __CLASS__,
                    "signalHandler"
                ], false );
                pcntl_signal( SIGUSR1, [
                    __CLASS__,
                    "signalHandler"
                ], false );
                break;

            case 'chanHandler':
                pcntl_signal( SIGCHLD, SIG_IGN, false );
                pcntl_signal( SIGTERM, SIG_IGN, false );
                pcntl_signal( SIGINT, SIG_IGN, false );
                pcntl_signal( SIGQUIT, SIG_IGN, false );
                pcntl_signal( SIGUSR1, SIG_IGN, false );
                pcntl_signal( SIGTSTP, SIG_IGN, false );


                pcntl_signal( SIGUSR2, [
                    __CLASS__,
                    "signalHandler"
                ], false );

                break;
            default:
                pcntl_signal( SIGCHLD, [
                    __CLASS__,
                    "signalHandler"
                ], false );
                pcntl_signal( SIGTERM, [
                    __CLASS__,
                    "signalHandler"
                ], false );
                pcntl_signal( SIGINT, [
                    __CLASS__,
                    "signalHandler"
                ], false );
                pcntl_signal( SIGQUIT, [
                    __CLASS__,
                    "signalHandler"
                ], false );
                pcntl_signal( SIGUSR2, [
                    __CLASS__,
                    "signalHandler"
                ], false );
                pcntl_signal( SIGTSTP, SIG_IGN, false );

        }
    }


    /**
     * signalHandler 进程信号处理
     * @author Louis
     * @param int $signal
     * @return void
     */
    public function signalHandler( int $signal )
    {
        Log::Dump(static::LOG_PREFIX."got a signal {$signal} : ".Process::SignalName($signal));
        switch ( $signal )
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
            case SIGUSR2:
                //剩余队列消费专用
                static::$terminate = true;
                break;
            default:
                return ;
        }

    }


    /**
     * _setProcessName  进程名称设置
     * @author Louis
     * @param string $proName
     */
    private function _setProcessName( string $proName )
    {
        Process::SetName( Daemon::$identity . '_Worker_' . $proName) ;
    }

    private function _setAlarm(int $lifecycle)
    {
        Process::SetAlarm( mt_rand( 30, $lifecycle ) );
    }


    /**
     * start 挂载信号处理、生成任务worker、开始worker监控
     * @author Louis
     */
    public function Start()
    {

        self::$jobNum = count( self::$jobs, 0 );

        if ( self::$jobNum == 0 )
        {
            Log::Dump( static::LOG_PREFIX . "please add one task at least." );
            $this->_exitMonitor();
        }
        $this->_setSignalHandler( 'monitorHandler' );
        $this->_forkWorkers();
        $this->_startMonitor();
    }

    /**
     * _exitWorkers 循环退出所有worker
     * @author Louis
     */
    private function _exitWorkers()
    {
        foreach ( static::$pidMap as $pid => $groupId )
        {
            if ( !Process::Kill( $pid, SIGUSR1 ) )
            {
                Process::Kill( $pid, SIGUSR1 );
            }
            usleep( 10000 );
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
            if ( self::$terminate )
            {
                //给工作进程发送退出信号
                $this->_exitWorkers();
                //等待进程退出
                $this->_waitUnexitedProcess();
                //开启管道读取进程并等待其退出
                $this->_startChanFinishProcess();
                //退出监控进程相关操作
                $this->_exitMonitor()();
            }

            pcntl_signal_dispatch();

            $status = 0;
            //returns the process ID of the child which exited, -1 on error or zero if WNOHANG was provided as an option (on wait3-available systems) and no child was available
            $pid = Process::Wait( $status );
            pcntl_signal_dispatch();
            $this->_handleExited( $pid, $status, false );

        }
    }

    /**
     * _waitUnexitedProcess 等待未退出的进程退出
     * @author Louis
     */
    private function _waitUnexitedProcess()
    {
        //统计未退出进程数
        $unExitedCount = count( static::$pidMap );

        //等待未退出进程退出
        for ( $i = 0; $i < $unExitedCount; $i++ )
        {
            $status = 0;
            RE_WAIT:
            $pid = Process::Wait( $status, WUNTRACED );
            if ( $pid == -1 )
            {
                goto RE_WAIT;
            }

            $this->_handleExited( $pid, $status );
        }
    }

    /**
     * _startChanFinishProcess 开启管道读取进程并等待其退出
     * @author Louis
     */
    private function _startChanFinishProcess()
    {
        //开启最终队列消费进程
        $this->_startChannelFinishProcess();

        $processesExitSign = [];
        for ( $i = 0; $i < static::$jobNum; $i++ )
        {
            if ( !self::$jobs[$i]['isChanReadProc'] )
            {
                $processesExitSign[$i] = true;
                continue;
            }
            $processesExitSign[$i] = false;
        }

        //wait for to be exited process
        $consumeProcessNum = count( static::$consumePidMap );
        for ( $i = 0; $i < $consumeProcessNum; $i++ )
        {
            $status = 0;

            RETRY:
            $pid = Process::Wait( $status, WUNTRACED );
            if ( $pid == -1 )
            {
                goto RETRY;
            }

            //兼容mac 拿到的子进程pid比原始pid少10的bug
            if ( !isset( static::$consumePidMap[$pid] ) )
            {
                if ( !isset( static::$consumePidMap[$pid + 10] ) )
                {
                    continue;
                }
                $pid = $pid + 10;
            }

            //通过退出的进程id获取 对应的进程组id
            $groupId = static::$consumePidMap[$pid];

            //change sign for exited process
            $processesExitSign[$groupId] = true;
            $this->_sendExitedSignalToConsumer( $processesExitSign, $groupId );
            Log::Dump( static::LOG_PREFIX .
                       "chan-consumer: " .
                       self::$jobs[$groupId]["processName"] .
                       "({$pid}) exited at status : {$status}"
            );
        }
        Log::Dump( static::LOG_PREFIX . "chan-consumer are all exited." );
    }


    /**
     * @param array $processesExitSign
     * @param int   $groupId
     */
    private function _sendExitedSignalToConsumer( array $processesExitSign, int $groupId )
    {
        $consumerId = $groupId + 1;

        //consumer process does not exists
        if ( !isset( $processesExitSign[$consumerId] ) )
        {
            return;
        }

        //consumer is already exited
        if ( $processesExitSign[$consumerId] )
        {
            return;
        }

        foreach ( self::$consumePidMap as $pid => $eachConsumerId )
        {
            //退出的进程组非某消费队列对应的生产队列
            if ( $eachConsumerId != $consumerId )
            {
                continue;
            }

            Log::Dump( self::LOG_PREFIX.'Sending SIGUSR2 to chan consumer process ' . $pid );
            for ( $i = 0; $i < 3; $i++ )
            {
                if ( Process::Kill( $pid, SIGUSR2 ) )
                {
                    break;
                }
            }

        }

    }


    /**
     * _handleExited 处理退出的进程
     * @author Louis
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

        $taskId = self::$pidMap[$pid];
        unset( self::$pidMap[$pid] );

        Log::Dump( self::LOG_PREFIX .
                   self::$jobs[$taskId]["processName"] .
                   "({$pid}) exited at status {$status}"
        );
        usleep(10000);

        //监控进程收到退出信号时则无需开启新的worker
        if ( !$isExit )
        {
            $this->_forkOneWorker( $taskId );
        }

    }


    /**
     * _forkWorkers 给多有任务开启对应任务执行worker组
     * @author Louis
     */
    private function _forkWorkers()
    {
        for ( $i = 0; $i < self::$jobNum; $i++ )
        {
            $this->_forkOneWorker( $i );
            usleep( 10000 );
        }
    }


    /**
     * _forkOneWork 生成一个任务worker
     * @author Louis
     * @param int $taskId
     */
    private function _forkOneWorker( int $taskId )
    {
        $pid = Process::Fork();

        if ( $pid > 0 )
        {
            self::$pidMap[$pid] = $taskId;
        }
        else if ( $pid == 0 )
        {
            $this->_runWorker( $taskId, static::LIFE_CYCLE );
        }
        else
        {
            sleep( 1 );
        }
    }


    /**
     * _runWorker 常驻执行任务
     * @author Louis
     * @param int $index
     * @param int $lifecycle
     */
    private function _runWorker( int $index, int $lifecycle )
    {
        Log::Dump(self::LOG_PREFIX.'starting '.self::$jobs[$index]['processName'].'('.Process::Id().')');
        $this->_setSignalHandler( 'workerHandler' );
        $this->_setAlarm($lifecycle);
        $this->_setProcessName( self::$jobs[$index]['processName'] );
        Process::SetExecGroupUser( self::$_group, self::$_user);
        Runtime::enableCoroutine();
        Coroutine::Create(function () use ($index) {
            Log::SetLogId();
            Component::Init(self::$jobs[$index]['components']);
        });
        Coroutine::Wait();
        $this->_runProcessTask( $index );
    }

    /**
     * _runProcessTask 进程形式执行任务
     * @author Louis
     * @param int $index
     */
    private function _runProcessTask( int $index )
    {
        $timeStart = time();
        Log::Dump( self::LOG_PREFIX . 'process : ' . self::$jobs[$index]['processName'] . ' started.' );

        while ( self::$jobs[$index]['coCount'] < self::$jobs[$index]['coQuantity'] )
        {
            Coroutine::Create( function () use ( $index, $timeStart )
            {
                $pid       = Process::Id();
                $coId      = Coroutine::Id();
                $execCount = 0;
                while ( true )
                {
                    if ( self::$terminate )
                    {
                        break;
                    }
                    Log::SetLogId(date('YmdHis').$pid.$coId.mt_rand(100,999));
                    pcntl_signal_dispatch();
                    if ( isset( self::$jobs[$index]['argv'] ) )
                    {
                        call_user_func_array( self::$jobs[$index]['function'], self::$jobs[$index]['argv'] );
                    }
                    else
                    {
                        call_user_func( self::$jobs[$index]['function'] );
                    }
                    $execCount++;
                    pcntl_signal_dispatch();

                    //release components resource after finish one work
                    Component::Release();
                }

                $execTimeSpan = time() - $timeStart;
                Log::Dump( self::LOG_PREFIX .
                               self::$jobs[$index]['processName'] .
                               " chan( {$index} ) finished {$execCount} times / {$execTimeSpan} S." );
            } );
            self::$jobs[$index]['coCount']++;
        }

        Coroutine::Wait();
        Log::DumpExit( self::LOG_PREFIX .
                       self::$jobs[$index]['processName'] .
                       " exited" );
    }

    /**
     * _startChannelFinishProcess 开启最终队列消费进程组
     */
    private function _startChannelFinishProcess()
    {
        Log::Dump( self::LOG_PREFIX . "starting chan consumer Process" );

        $newGroupNum = 0;
        for ( $i = 0; $i < self::$jobNum; $i++ )
        {
            if ( !self::$jobs[$i]['isChanReadProc'] )
            {
                continue;
            }

            $pid = Process::Fork();
            if ( $pid > 0 )
            {
                self::$consumePidMap[$pid] = $i;
            }
            else if ( $pid == 0 )
            {
                //重置退出标识为不退出
                self::$terminate = false;
                $this->_setSignalHandler( 'chanHandler' );
                $this->_setProcessName( self::$jobs[$i]['processName'] );
                Process::SetExecGroupUser( self::$_group, self::$_user);
                Log::Dump( self::LOG_PREFIX . 'chan-consumer ' . self::$jobs[$i]['processName'] . ' starting work' );

                while ( self::$jobs[$i]['coCount'] < self::$jobs[$i]['coQuantity'] )
                {
                    self::$jobs[$i]['coCount']++;

                    Coroutine::Create(function() use($i, $newGroupNum) {
                        $this->_consumeChanTask($i, $newGroupNum);
                    });
                }
                Coroutine::Wait();
                Log::DumpExit( self::LOG_PREFIX .
                           "chan-finish process " .
                           self::$jobs[$i]['processName'] .
                           ' exited ' );
            }

            $newGroupNum++;
            usleep( 1000 );
        }
        Log::Dump( json_encode( self::$consumePidMap ) );
        Log::Dump( self::LOG_PREFIX . "chan-consumer are all started." );
    }

    /**
     * @author Louis
     * @param int $index
     * @param int $newJobIndex
     */
    private function _consumeChanTask( int $index, int $newJobIndex )
    {
        $timeStart = time();
        $workCount = 0;
        $pid  = Process::Id();
        $coId = Coroutine::Id();
        $retryTimes = 0;
        while ( 1 )
        {
            Log::SetLogId(date('YmdHis').$pid.$coId.mt_rand(100,999));
            pcntl_signal_dispatch();

            if ( isset( self::$jobs[$index]['argv'] ) )
            {
                $channelStatus = call_user_func_array( self::$jobs[$index]['function'], self::$jobs[$index]['argv'] );
            }
            else
            {
                $channelStatus = call_user_func( self::$jobs[$index]['function'] );
            }

            pcntl_signal_dispatch();

            $workCount['count']++;

            if ( $channelStatus )
            {
                $retryTimes = 0;
                continue;
            }
            //队列为空 && 重试后依然为空 && （已收到可退出信号  || 当前进程为第一组消费队列，即无生产队列）
            if ( !$channelStatus && $retryTimes > 0 && ($newJobIndex == 0 || self::$terminate) )
            {
                break;
            }

            if ( !$channelStatus && ($newJobIndex == 0 || self::$terminate) )
            {
                $retryTimes++;
            }

        }
        $timeEnd          = time();
        $proWorkerTimeSum = $timeEnd - $timeStart;
        Log::Dump( self::LOG_PREFIX .
                       "chan-finish coroutine( {$coId} ) " .
                       self::$jobs[$index]['processName'] .
                       ' finished ' .
                       $workCount .
                       ' times / ' .
                       $proWorkerTimeSum .
                       ' S.' );
    }


    /**
     * _exitMonitor 删除进程pid文件、记录退出信息后正常退出粗
     * @author Louis
     */
    private function _exitMonitor()
    {
        Log::Dump( static::LOG_PREFIX . "monitor exited." );
        exit(0);
    }

    /**
     * addTask 添加任务及相关属性
     * @author Louis
     * @param array $job
     */
    public function AddTask( $job = [] )
    {

        if ( !isset( $job['function'] ) || empty( $job['function'] ) )
        {
            Log::DumpExit( self::LOG_PREFIX . " one Task at least is needed." );
        }

        $job['coCount']        = 0;
        $job['coQuantity']     = (isset( $job['coQuantity'] ) && (int)$job['coQuantity'] > 0)    ? $job['coQuantity'] : self::COROUTINE_QUANTITY;
        $job['processName']    = (isset( $job['procName'] )   && !empty( $job['procName'] ))     ? $job['procName']   : self::PROCESS_NAME;
        $job['isChanReadProc'] = isset( $job['isChanReadProc'] ) && true==$job['isChanReadProc'] ? true : false;
        $job['components']     = isset( $job['components'] ) && is_array($job['components']) ?  $job['components'] : [];
        self::$jobs[]          = $job;
    }

}

