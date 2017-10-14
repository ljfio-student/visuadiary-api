<?php
// Main Routes
$app->get('/', '\Site\Controller\MainController:index')->setName('index');