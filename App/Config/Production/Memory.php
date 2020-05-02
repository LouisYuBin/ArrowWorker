<?php

return [
    'default' => [
        'size'   => 10000,
        'column' => [
            'id'        => 'int',
            'token'     => 'string',
            'name'      => 'string',
            'loginTime' => 'string',
        ],
    ],
    'clients' => [
        'size'   => 100000,
        'column' => [
            'id'        => 'int',
            'token'     => 'string',
            'name'      => 'string',
            'loginTime' => 'string',
        ],
    ],
];