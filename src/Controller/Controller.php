<?php

namespace Site\Controller;

use Psr\Container\ContainerInterface as Container;

class Controller
{
    private $container;

    public function __constructor(Container $container) {
        $this->container = $container;
    }
}
