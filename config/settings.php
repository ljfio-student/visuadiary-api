<?php
return [
    'settings' => [
        'displayErrorDetails'    => true, // set to false in production
        'addContentLengthHeader' => false, // Allow the web server to send the content-length header

        // Monolog settings
        'logger'                 => [
            'name'  => 'slim-app',
            'path'  => __DIR__ . '/../logs/app.log',
            'level' => \Monolog\Logger::DEBUG,
        ],

        // AWS settings
        'aws'                    => [
            'bucket'      => 'visuadiarybucket',
            // Simple Storage Service
            's3'          => [
                'profile' => 'visuadiary',
                'region'  => 'eu-west-1',
            ],

            // Rekognition
            'rekognition' => [
                'profile' => 'visuadiary',
                'region'  => 'eu-west-1',
            ],
        ],

        // Database
        'database'               => [
            'driver'    => 'mysql',
            'host'      => 'localhost',
            'database'  => 'visuadiary',
            'username'  => 'root',
            'password'  => '',
            'charset'   => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix'    => '',
        ],
    ],
];
