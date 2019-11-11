<?php

return [
    //驱动类型
    'driver' => 'ArrowDaemon',
    'user'   => 'www',
    'group'  => 'www',
    'worker' => [
        [
            'procName'        => 'producer',
            'function'        => [
                '\\App\\Controller\\Demo',
                'Demo',
            ],
            'argv'            => [ 100 ],
            'processQuantity' => 1,
            'coQuantity'      => 10,
            'components'      => [
                'db'    => [
                    'default' => 10,
                ],
                'cache' => [
                    'default' => 5,
                ],
            ],

        ],
        [
            'procName'        => 'consumer_1',
            'function'        => [
                '\\App\\Controller\\Demo',
                'channelApp',
            ],
            'argv'            => [ 100 ],
            'processQuantity' => 1,
            'coQuantity'      => 100,
            'components'      => [
                'db'    => [
                    'default' => 100,
                ],
                'cache' => [
                    'default' => 100,
                ],
            ],
        ],
        [
            'procName'        => 'consumer_2',
            'function'        => [
                '\\App\\Controller\\Demo',
                'channelArrow',
            ],
            'argv'            => [ 100 ],
            'processQuantity' => 1,
            'coQuantity'      => 100,
            'components'      => [
                'db'    => [
                    'default' => 100,
                ],
                'cache' => [
                    'default' => 100,
                ],
            ],
        ],
        [
            'procName'        => 'consumer_3',
            'function'        => [
                '\\App\\Controller\\Demo',
                'channeltest',
            ],
            'argv'            => [ 100 ],
            'processQuantity' => 1,
            'coQuantity'      => 100,
            'components'      => [
                'db'    => [
                    'default' => 100,
                ],
                'cache' => [
                    'default' => 100,
                ],
            ],
        ],
    ],
];