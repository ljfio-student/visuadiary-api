<?php

// Main Routes
$app->post('/login', '\Site\Controller\SessionController:login');
$app->post('/register', '\Site\Controller\SessionController:register');

$app->get('/visitor/{id}', '\Site\Controller\VisitorController:get');
$app->post('/visitor', '\Site\Controller\VisitorController:add');
$app->put('/visitor/{id}', '\Site\Controller\VisitorController:update');
$app->delete('/visitor/{id}', '\Site\Controller\VisitorController:remove');
$app->post('/visitor/{id}/image', '\Site\Controller\ImageController:visitor');

$app->get('/profile', '\Site\Controller\ProfileController:get');
$app->put('/profile', '\Site\Controller\ProfileController:update');
$app->post('/profile/image', '\Site\Controller\ImageController:profile');

$app->post('/entry', '\Site\Controller\ImageController:entry');

$app->get('/diary', '\Site\Controller\DiaryController:list');
$app->get('/diary/:id', '\Site\Controller\DiaryController:get');