<?php

return [
    'app' => [
        //驱动类型
        'driver' => 'ArrowDaemon',

        'processor' => [
            [
                //function to be call
                'function'    => ['\\App\\Controller\\Demo','Demo'],
                'argv'        => [100],
                //number of process to be started
                'procQuantity' => 1,
                //process name
                'procName'     => 'Demo'

            ],
            [
                'function'       => ['\\App\\Controller\\Demo','channelApp'],
                'argv'           => [100],
                'procName'        => 'channelApp',
                'procQuantity'   => 3,
                'isChanReadProc' => true,
            ],
            [
                'function'       => ['\\App\\Controller\\Demo','channelArrow'],
                'argv'           => [100],
                'procName'       => 'channelArrow',
                'procQuantity'   => 3,
                'isChanReadProc' => true,
            ],
            [
                'function'       => ['\\App\\Controller\\Demo','channeltest'],
                'argv'           => [100],
                'procName'        => 'channeltest',
                'procQuantity'    => 3,
                'isChanReadProc' => true,
            ],
        ]
    ]
];