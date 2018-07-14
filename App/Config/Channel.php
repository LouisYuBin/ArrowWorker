<?php

return [
    'app' => [
        //驱动类型
        'driver' => 'Queue',
        //最大读取长度
        'msgSize'   => 128,
        //队列占用byte大小设置
        'bufSize' => 10240000
    ],
    'arrow' => [
        //驱动类型
        'driver' => 'Queue',
        //最大读取长度
        'msgSize'   => 128,
        //队列占用byte大小设置
        'bufSize' => 10240000
    ],
    'test' => [
        //驱动类型
        'driver' => 'Queue',
        //最大读取长度
        'msgSize'   => 128,
        //队列占用byte大小设置
        'bufSize' => 10240000
    ]
];