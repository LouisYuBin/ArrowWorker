<?php

return [
    //驱动类型
    'driver' => 'ArrowDaemon',
    'user'   => 'www',
    'group'  => 'www',
    'worker' => [
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
                ],
                'cache' => [
                    'default' => 2
                ],
                'tcp_client' => [
                    'default' => 3,
                    'conner'  => 2
                ],
                'ws_client' => [
                    'default' => 2
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
                    'default' => 5
                ],
                'cache' => [
                    'default' => 5
                ],
                'tcp_client' => [
                    'default' => 3,
                    'conner'  => 2
                ],
                'ws_client' => [
                    'default' => 2
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
                    'default' => 5
                ],
                'cache' => [
                    'default' => 5
                ],
                'tcp_client' => [
                    'default' => 3,
                    'conner'  => 2
                ],
                'ws_client' => [
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
                    'default' => 5
                ],
                'cache' => [
                    'default' => 5
                ],
                'tcp_client' => [
                    'default' => 3,
                    'conner'  => 2
                ],
                'ws_client' => [
                    'default' => 2
                ]
            ]
        ],
    ]
];