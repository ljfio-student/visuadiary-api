<?php

// Main Routes
$app->get('/', '\Site\Controller\MainController:index')->setName('index');
$app->post('/image/upload', '\Site\Controller\ImageController:upload');