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
                '\\App\\Demo\\Controller\\Demo',
                'channelApp',
            ],
            'argv'            => [ 100 ],
            'processQuantity' => 1,
            'coQuantity'      => 20,
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
            'procName'        => 'consumer_2',
            'function'        => [
                '\\App\\Demo\\Controller\\Demo',
                'channelArrow',
            ],
            'argv'            => [ 100 ],
            'processQuantity' => 1,
            'coQuantity'      => 50,
            'components'      => [
                'db'    => [
                    'default' => 50,
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
            'processQuantity' => 1,
            'coQuantity'      => 150,
            'components'      => [
                'db'    => [
                    'default' => 150,
                ],
                'cache' => [
                    'default' => 150,
                ],
            ],
        ],
    ],
];