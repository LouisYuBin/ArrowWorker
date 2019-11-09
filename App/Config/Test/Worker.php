<?php

return [
    //驱动类型
    'driver' => 'ArrowDaemon',
    'user'   => 'www',
    'group'  => 'www',
    'worker' => [
        [
            'procName'        => 'Demo',
            'function'        => [
                '\\App\\Controller\\Demo',
                'Demo',
            ],
            'argv'            => [ 100 ],
            'processQuantity' => 1,
            'coQuantity'      => 1,
            'components'      => [
                'db'    => [
                    'default' => 20,
                ],
                'cache' => [
                    'default' => 200,
                ],
            ],

        ],
        [
            'procName'        => 'channelApp',
            'function'        => [
                '\\App\\Controller\\Demo',
                'channelApp',
            ],
            'argv'            => [ 100 ],
            'processQuantity' => 3,
            'coQuantity'      => 100,
            'components'      => [
                'db'    => [
                    'default' => 20,
                ],
                'cache' => [
                    'default' => 20,
                ],
            ],
        ],
        [
            'procName'        => 'channelArrow',
            'function'        => [
                '\\App\\Controller\\Demo',
                'channelArrow',
            ],
            'argv'            => [ 100 ],
            'processQuantity' => 3,
            'coQuantity'      => 100,
            'components'      => [
                'db'    => [
                    'default' => 1,
                ],
                'cache' => [
                    'default' => 20,
                ],
            ],
        ],
        [
            'procName'        => 'channeltest',
            'function'        => [
                '\\App\\Controller\\Demo',
                'channeltest',
            ],
            'argv'            => [ 100 ],
            'processQuantity' => 10,
            'coQuantity'      => 100,
            'components'      => [
                'db'    => [
                    'default' => 1,
                ],
                'cache' => [
                    'default' => 20,
                ],
            ],
        ],
    ],
];