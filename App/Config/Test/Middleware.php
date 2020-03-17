<?php

return [
    'api.dugujiujian.com, 127.0.0.1' => [
        '/manager/*' =>  [
	        App\Middleware\Authorization::class
        ]
    ]
];
