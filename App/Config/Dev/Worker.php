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
                '\\App\\Demo\\Controller\\Demo',
                'Demo',
            ],
            'argv'            => [ 100 ],
            'processQuantity' => 3,
            'coQuantity'      => 5,
            'components'      => [
                'db'    => [
                    'default' => 5,
                ],
                'cache' => [
                    'default' => 5,
                ],
            ],

        ],
        [
            'procName'        => 'consumer_1',
            'function'        => [
                '\\App\\Demo\\Controller\\Demo',
                'channelApp',
            ],
            'argv'            => [ 100 ],
            'processQuantity' => 3,
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
                '\\App\\Demo\\Controller\\Demo',
                'channelArrow',
            ],
            'argv'            => [ 100 ],
            'processQuantity' => 3,
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
                '\\App\\Demo\\Controller\\Demo',
                'channeltest',
            ],
            'argv'            => [ 100 ],
            'processQuantity' => 3,
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