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
            'coQuantity' => 1,
            'procName'   => 'Demo',
            'components' => [
                'db' => [
                    'default' => 5
                ]
            ]

        ],
        [
            'function'       => [
                '\\App\\Controller\\Demo',
                'channelApp'
            ],
            'argv'           => [ 100 ],
            'procName'       => 'channelApp',
            'coQuantity'     => 5,
            'isChanReadProc' => true,
            'components' => [
                'db' => [
                    'default' => 3
                ]
            ]
        ],
        [
            'function'       => [
                '\\App\\Controller\\Demo',
                'channelArrow'
            ],
            'argv'           => [ 100 ],
            'procName'       => 'channelArrow',
            'coQuantity'     => 5,
            'isChanReadProc' => true,
            'components' => [
                'db' => [
                    'default' => 2
                ]
            ]
        ],
        [
            'function'       => [
                '\\App\\Controller\\Demo',
                'channeltest'
            ],
            'argv'           => [ 100 ],
            'procName'       => 'channeltest',
            'coQuantity'     => 5,
            'isChanReadProc' => true,
            'components' => [
                'db' => [
                    'default' => 2
                ]
            ]
        ],
    ]
];