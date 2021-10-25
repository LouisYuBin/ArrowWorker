<?php
/**
 * By yubin at 2019-10-18 18:29.
 */

namespace ArrowWorker\Library;

use ArrowWorker\Log\Log;


/**
 * Class Process
 * @package ArrowWorker\Library
 */
class Process
{

    /**
     *
     */
    private const MODULE_NAME = 'Process';

    /**
     *
     */
    private const SIGNAL_COMMON_MAP = [
        's1'  => 'SIGHUP',
        's2'  => 'SIGINT',    //Ctrl-C
        's3'  => 'SIGQUIT',
        's4'  => 'SIGILL',
        's5'  => 'SIGTRAP',
        's6'  => 'SIGIOT',
        's8'  => 'SIGFPE',
        's9'  => 'SIGKILL',
        's13' => 'SIGPIPE',
        's14' => 'SIGALRM',
        's15' => 'SIGTERM',
        's21' => 'SIGTTIN',
        's22' => 'SIGTTOU',
    ];

    /**
     *
     */
    private const SIGNAL_MAC_MAP = [
        's10' => 'SIGBUS',
        's30' => 'SIGUSR1',
        's31' => 'SIGUSR2',
        's20' => 'SIGCHLD',
        's19' => 'SIGCONT',
        's17' => 'SIGSTOP',
        's18' => 'SIGTSTP',
        's16' => 'SIGURG',
    ];

    /**
     *
     */
    private const SIGNAL_LINUX_MAP = [
        's7'  => 'SIGBUS',
        's10' => 'SIGUSR1',
        's12' => 'SIGUSR2',
        's17' => 'SIGCHLD',
        's18' => 'SIGCONT',
        's19' => 'SIGSTOP',
        's20' => 'SIGTSTP',
        's23' => 'SIGURG',
    ];

    /**
     * @var array
     */
    private static array $signalMap = [];

    /**
     * @var array
     */
    private static array $killNotificationPidMap = [];

    /**
     * @param string $name
     * @return void
     */
    public static function setName(string $name): void
    {
        if (PHP_OS === 'Darwin') {
            return;
        }

        if (function_exists('cli_set_process_title')) {
            @cli_set_process_title($name);
        }
        if (extension_loaded('proctitle') && function_exists('setproctitle')) {
            @setproctitle($name);
        }
    }

    /**
     * @return int
     */
    public static function id(): int
    {
        return posix_getpid();
    }

    /**
     * @return int
     */
    public static function fork(): int
    {
        return pcntl_fork();
    }

    /**
     * @param int $seconds
     * @return int
     */
    public static function setAlarm(int $seconds): int
    {
        return pcntl_alarm($seconds);
    }

    /**
     * @param int $status
     * @param int $options
     * @return int
     */
    public static function wait(int &$status, int $options = WUNTRACED): int
    {
        return pcntl_wait($status, $options);
    }

    /**
     * @param int $pid
     * @param int $signal
     * @param bool $isForceNotify
     * @return bool
     */
    public static function kill(int $pid, int $signal, bool $isForceNotify = false): bool
    {
        if ($isForceNotify) {
            goto KILL;
        }

        if (self::isKillNotified($pid . $signal)) {
            return true;
        }

        KILL:
        if (posix_kill($pid, $signal)) {
            self::$killNotificationPidMap[] = $pid . $signal;
            return true;
        }
        return false;
    }

    /**
     * @param string $pidSignal
     * @return bool
     */
    public static function isKillNotified(string $pidSignal): bool
    {
        return in_array($pidSignal, self::$killNotificationPidMap, true);
    }

    /**
     * 获取进程信号名称
     * @param int $signal
     * @return string
     */
    public static function getSignalName(int $signal): string
    {
        if (0 === count(self::$signalMap)) {
            self::$signalMap = PHP_OS === 'Darwin' ?
                array_merge(self::SIGNAL_COMMON_MAP, self::SIGNAL_MAC_MAP) :
                array_merge(self::SIGNAL_COMMON_MAP, self::SIGNAL_LINUX_MAP);
        }

        $key = 's' . $signal;
        if (!isset(self::$signalMap[$key])) {
            return 'unknown';
        }
        return self::$signalMap[$key];
    }

    /**
     * @param int $seconds
     */
    public static function sleep(int $seconds): int
    {
        return sleep($seconds);
    }

    /**
     * @param string $group
     * @param string $user
     */
    public static function setExecGroupUser(string $group, string $user): void
    {
        $user  = posix_getpwnam($user);
        $group = posix_getgrnam($group);

        if (!$user || !$group) {
            Log::Dump(__CLASS__ . '::' . __METHOD__ . ", posix_getpwnam({$user})/posix_getgrnam({$group}) failed！", Log::TYPE_NOTICE, self::MODULE_NAME);
        }

        if (!posix_setgid($group['gid']) || !posix_setuid($user['uid'])) {
            Log::Dump(__CLASS__ . '::' . __METHOD__ . ",  posix_setuid({$user['uid']})/posix_setgid({$group['gid']}) failed！", Log::TYPE_NOTICE, self::MODULE_NAME);
        }
    }

}