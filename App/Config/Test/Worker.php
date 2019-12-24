<?php

return [
    //驱动类型
    'driver' => 'ArrowDaemon',
    'user'   => 'www',
    'group'  => 'www',
    'worker' => [
        [
            'name'        => 'producer',
            'function'        => 'Demo\\Demo@Demo',
            'argv'            => [ 100 ],
            'processQuantity' => 1,
            'coQuantity'      => 50,
            'components'      => [
                'db'    => [
                    'default' => 50,
                ],
                'cache' => [
                    'default' => 50,
                ],
            ],

        ],
        [
            'name'        => 'consumer_1',
            'function'        => 'Demo\\Demo@channelApp',
            'argv'            => [ 100 ],
            'processQuantity' => 2,
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
            'name'        => 'consumer_2',
            'function'        => 'Demo\\Demo@channelArrow',
            'argv'            => [ 100 ],
            'processQuantity' => 2,
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
            'name'        => 'consumer_3',
            'function'        => 'Demo\\Demo@channeltest',
            'argv'            => [ 100 ],
            'processQuantity' => 2,
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