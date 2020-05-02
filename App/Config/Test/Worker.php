<?php

return [
    //驱动类型
    'driver' => 'ArrowDaemon',
    'user'   => 'www',
    'group'  => 'www',
    'worker' => [
        [
            'name'            => 'producer',
            'callback'        => [
                \App\Controller\Demo\Demo::class,
                'Demo',
            ],
            'argv'            => [100],
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
            'name'            => 'consumer_1',
            'callback'        => [
                \App\Controller\Demo\Demo::class,
                'channelApp',
            ],
            'argv'            => [100],
            'processQuantity' => 2,
            'coQuantity'      => 200,
            'components'      => [
                'db'    => [
                    'default' => 200,
                ],
                'cache' => [
                    'default' => 200,
                ],
            ],
        ],
        [
            'name'            => 'consumer_2',
            'callback'        => [
                \App\Controller\Demo\Demo::class,
                'channelArrow',
            ],
            'argv'            => [100],
            'processQuantity' => 2,
            'coQuantity'      => 200,
            'components'      => [
                'db'    => [
                    'default' => 200,
                ],
                'cache' => [
                    'default' => 200,
                ],
            ],
        ],
        [
            'name'            => 'consumer_3',
            'callback'        => [
                \App\Controller\Demo\Demo::class,
                'channeltest',
            ],
            'argv'            => [100],
            'processQuantity' => 2,
            'coQuantity'      => 200,
            'components'      => [
                'db'    => [
                    'default' => 200,
                ],
                'cache' => [
                    'default' => 200,
                ],
            ],
        ],
    ],
];