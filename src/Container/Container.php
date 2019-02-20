<?php

namespace Asdfx\Tight\Container;

class Container
{
    protected static $instance;

    protected $instances = [];

    public function instance($abstract, $instance)
    {
        $this->instances[$abstract] = $instance;
    }
}