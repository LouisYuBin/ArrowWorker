<?php
/**
 * By yubin at 2019-09-02 17:35.
 */

namespace ArrowWorker;


class Component
{
    public static function Init(array $components)
    {
        var_dump($components);
        foreach ( $components as $component )
        {
            $component = strtoupper($component);
            switch ($component)
            {
                case 'DB':
                    var_dump('Db::Init');
                    Db::Init();
                    break;
            }
        }
    }

    public static function Release()
    {
        Db::Release();
    }

}