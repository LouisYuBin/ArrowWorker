<?php

return [
    //驱动类型
    'driver' => 'ArrowDaemon',
    'group'  => [
        [
            'function'   => [
                '\\App\\Controller\\Demo',
                'Demo'
            ],
            'argv'       => [ 100 ],
            'coQuantity' => 5000,
            'procName'   => 'Demo'

        ],
        [
            'function'       => [
                '\\App\\Controller\\Demo',
                'channelApp'
            ],
            'argv'           => [ 100 ],
            'procName'       => 'channelApp',
            'coQuantity'     => 15000,
            'isChanReadProc' => true,
        ],
        [
            'function'       => [
                '\\App\\Controller\\Demo',
                'channelArrow'
            ],
            'argv'           => [ 100 ],
            'procName'       => 'channelArrow',
            'coQuantity'     => 15000,
            'isChanReadProc' => true,
        ],
        [
            'function'       => [
                '\\App\\Controller\\Demo',
                'channeltest'
            ],
            'argv'           => [ 100 ],
            'procName'       => 'channeltest',
            'coQuantity'     => 15000,
            'isChanReadProc' => true,
        ],
    ]
];