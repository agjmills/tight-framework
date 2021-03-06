<?php

namespace Asdfx\Tight;

use Asdfx\Tight\Container\Container;
use Asdfx\Tight\Routing\Router;

class Tight extends Container
{
    protected $basePath;

    public function __construct($basePath = null)
    {
        if ($basePath !== null) {
            $this->setBasePath($basePath);
        }
    }

    public static function boot($basePath)
    {
        if (!isset(self::$instance)) {
            self::$instance = new self($basePath);
        }

        return self::$instance;
    }

    private function setBasePath($basePath): void
    {
        $this->basePath = rtrim($basePath . DIRECTORY_SEPARATOR . '..', '\/');

        $this->bindPathsInContainer();
    }

    protected function bindPathsInContainer()
    {
        $this->instance('path.base', $this->getBasePath());
        $this->instance('path.config', $this->getConfigPath());
    }

    public function getConfigPath($path = 'config')
    {
        return $this->basePath . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }

    public function getBasePath($path = '')
    {
        return $this->basePath . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }

    public function getRouter(): Router
    {
        return new Router();
    }

    public function getInstance(?string $instance = null)
    {
        if ($instance === null) {
            return self::$instance;
        }

        if (array_key_exists($instance, $this->instances)) {
            return $this->instances[$instance];
        }

        return null;
    }
}
