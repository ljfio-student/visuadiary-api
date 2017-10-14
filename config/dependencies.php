<?php

// DIC configuration
$container = $app->getContainer();

// monolog
$container['logger'] = function ($c) {
    $settings = $c->get('settings')['logger'];

    $logger = new Monolog\Logger($settings['name']);
    $logger->pushProcessor(new Monolog\Processor\UidProcessor());
    $logger->pushHandler(new Monolog\Handler\StreamHandler($settings['path'], $settings['level']));

    return $logger;
};

$container['db'] = function ($c) {
    $settings = $c->get('settings')['database'];

    $capsule = new \Illuminate\Database\Capsule\Manager;
    $capsule->addConnection($settings);

    $capsule->setAsGlobal();
    $capsule->bootEloquent();

    return $capsule;
};

$container['s3'] = function ($c) {
    $settings = $c->get('settings')['aws']['s3'];

    $client = new Aws\S3\S3Client($settings);

    return $client;
};

$container['rekognition'] = function ($c) {
    $settings = $c->get('settings')['aws']['rekognition'];

    $client = new Aws\Rekognition\RekognitionClient($settings);

    return $client;
};