<?php
/**
 * By yubin at 2019-09-02 17:35.
 */

namespace ArrowWorker;


class Component
{
    public static function Init(array $components)
    {
        foreach ( $components as $key=>$config )
        {
            $component = strtoupper($key);
            switch ($component)
            {
                case 'DB':
                    Db::Init($config);
                    break;
            }
        }
    }

    public static function Release()
    {
        Db::Release();
    }

}