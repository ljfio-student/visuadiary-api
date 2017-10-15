<?php

namespace Site\Controller;

use Psr\Container\ContainerInterface as Container;

class Controller
{
    protected $container;

    public function __construct(Container $container) {
        $this->container = $container;
    }
}
