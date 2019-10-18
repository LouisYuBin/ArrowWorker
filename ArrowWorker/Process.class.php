<?php
/**
 * By yubin at 2019-10-18 18:29.
 */

namespace ArrowWorker;


class Process
{

    const LOG_PREFIX = '[ Process ] ';

    public static function SetName(string $name)
    {
        if( PHP_OS=='Darwin')
        {
            return ;
        }

        if(function_exists('cli_set_process_title'))
        {
            @cli_set_process_title($name);
        }
        if(extension_loaded('proctitle') && function_exists('setproctitle'))
        {
            @setproctitle($name);
        }
    }

    public static function Id() : int
    {
        return posix_getpid();
    }

    public static function Fork()
    {
        return pcntl_fork();
    }

    public static function SetAlarm(int $seconds)
    {
        pcntl_alarm( $seconds );
    }

    public static function Wait(int &$status,int $options=WUNTRACED) : int
    {
        return pcntl_wait($status, $options);
    }

    public static function Kill(int $pid, int $sign) : bool
    {
        return posix_kill( $pid, $sign );
    }

    public static function SetExecGroupUser(string $group, string $user)
    {
        $user  = posix_getpwnam( $user );
        $group = posix_getgrnam( $group );

        if( !$user || !$group )
        {
            Log::Dump(self::LOG_PREFIX. ' '.__FUNCTION__.", posix_getpwnam({$user})/posix_getgrnam({$group}) failed！");
        }

        if( !posix_setuid($user['uid']) || !posix_setgid($group['gid']) )
        {
            Log::Dump(self::LOG_PREFIX. ' '.__FUNCTION__.",  posix_setuid({$user['uid']})/posix_setgid({$group['gid']}) failed！");
        }
    }

}