<?php

namespace Asdfx\Tight\Routing;

class Router
{
    private $afterRoutes = [];
    private $beforeRoutes = [];
    protected $notFoundCallback;
    private $baseRoute = '';
    private $requestedMethod = '';
    private $serverBasePath;
    private $namespace = '';

    public function before($methods, $pattern, $function): void
    {
        $pattern = $this->baseRoute . '/' . trim($pattern, '/');
        $pattern = $this->baseRoute ? rtrim($pattern, '/') : $pattern;
        foreach (explode('|', $methods) as $method) {
            $this->beforeRoutes[$method][] = [
                'pattern' => $pattern,
                'function' => $function,
            ];
        }
    }

    public function match($methods, $pattern, $function): void
    {
        $pattern = $this->baseRoute . '/' . trim($pattern, '/');
        $pattern = $this->baseRoute ? rtrim($pattern, '/') : $pattern;
        foreach (explode('|', $methods) as $method) {
            $this->afterRoutes[$method][] = [
                'pattern' => $pattern,
                'function' => $function,
            ];
        }
    }

    public function all($pattern, $function): void
    {
        $this->match('GET|POST|PUT|DELETE|OPTIONS|PATCH|HEAD', $pattern, $function);
    }

    public function get(string $pattern, $function): void
    {
        $this->match('GET', $pattern, $function);
    }

    public function post(string $pattern, $function): void
    {
        $this->match('POST', $pattern, $function);
    }

    public function patch(string $pattern, $function): void
    {
        $this->match('PATCH', $pattern, $function);
    }

    public function delete(string $pattern, $function): void
    {
        $this->match('DELETE', $pattern, $function);
    }

    public function put(string $pattern, $function): void
    {
        $this->match('PUT', $pattern, $function);
    }

    public function options(string $pattern, $function): void
    {
        $this->match('OPTIONS', $pattern, $function);
    }

    public function mount(string $baseRoute, $function): void
    {
        $curBaseRoute = $this->baseRoute;
        $this->baseRoute .= $baseRoute;
        call_user_func($function);
        $this->baseRoute = $curBaseRoute;
    }

    public function getRequestHeaders(): array
    {
        $headers = [];
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
            if ($headers !== false) {
                return $headers;
            }
        }

        foreach ($_SERVER as $name => $value) {
            if ((substr($name, 0, 5) == 'HTTP_') || ($name == 'CONTENT_TYPE') || ($name == 'CONTENT_LENGTH')) {
                $headers[
                    str_replace(
                        [' ', 'Http'],
                        ['-', 'HTTP'],
                        ucwords(strtolower(str_replace('_', ' ', substr($name, 5))))
                    )
                ] = $value;
            }
        }
        return $headers;
    }

    public function getRequestMethod(): string
    {
        $method = $_SERVER['REQUEST_METHOD'];
        if ($_SERVER['REQUEST_METHOD'] == 'HEAD') {
            ob_start();
            return 'GET';
        }

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $headers = $this->getRequestHeaders();
            if (isset($headers['X-HTTP-Method-Override'])
                && in_array($headers['X-HTTP-Method-Override'], ['PUT', 'DELETE', 'PATCH'])) {
                return $headers['X-HTTP-Method-Override'];
            }
        }

        return $method;
    }

    public function setNamespace($namespace): void
    {
        if (is_string($namespace)) {
            $this->namespace = $namespace;
        }
    }

    public function getNamespace(): string
    {
        return $this->namespace;
    }

    public function run($callback = null): bool
    {
        $this->requestedMethod = $this->getRequestMethod();
        if (isset($this->beforeRoutes[$this->requestedMethod])) {
            $this->handle($this->beforeRoutes[$this->requestedMethod]);
        }

        $numHandled = 0;
        if (isset($this->afterRoutes[$this->requestedMethod])) {
            $numHandled = $this->handle($this->afterRoutes[$this->requestedMethod], true);
        }
        if ($numHandled === 0) {
            if ($this->notFoundCallback) {
                $this->invoke($this->notFoundCallback);
                return false;
            }

            header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found');
            return false;
        }

        if ($callback && is_callable($callback)) {
            $callback();
        }

        if ($_SERVER['REQUEST_METHOD'] == 'HEAD') {
            ob_end_clean();
        }

        return $numHandled !== 0;
    }

    public function set404($function): void
    {
        $this->notFoundCallback = $function;
    }

    private function handle($routes, $quitAfterRun = false)
    {
        $routesHandled = 0;

        $uri = $this->getCurrentUri();

        foreach ($routes as $route) {
            $route['pattern'] = preg_replace('/\/{(.*?)}/', '/(.*?)', $route['pattern']);
            if (preg_match_all('#^' . $route['pattern'] . '$#', $uri, $matches, PREG_OFFSET_CAPTURE)) {
                $matches = array_slice($matches, 1);
                $params = array_map(function ($match, $index) use ($matches) {
                    if (isset($matches[$index + 1])
                        && isset($matches[$index + 1][0])
                        && is_array($matches[$index + 1][0])) {
                        return trim(substr($match[0][0], 0, $matches[$index + 1][0][1] - $match[0][1]), '/');
                    }

                    return isset($match[0][0]) ? trim($match[0][0], '/') : null;
                }, $matches, array_keys($matches));

                $this->invoke($route['function'], $params);
                ++$routesHandled;

                if ($quitAfterRun) {
                    break;
                }
            }
        }

        return $routesHandled;
    }

    private function invoke($function, $parameters = [])
    {
        if (is_callable($function)) {
            call_user_func_array($function, $parameters);
            return;
        }

        if (stripos($function, '@') !== false) {
            list($controller, $method) = explode('@', $function);
            if ($this->getNamespace() !== '') {
                $controller = $this->getNamespace() . '\\' . $controller;
            }

            if (class_exists($controller)) {
                if (call_user_func_array([new $controller(), $method], $parameters) === false) {
                    if (forward_static_call_array([$controller, $method], $parameters) === false) {
                    }
                }
            }
        }
    }

    protected function getCurrentUri(): string
    {
        $uri = substr($_SERVER['REQUEST_URI'], strlen($this->getServerBasePath()));

        if (strstr($uri, '?')) {
            $uri = substr($uri, 0, strpos($uri, '?'));
        }

        return '/' . trim($uri, '/');
    }

    protected function getServerBasePath(): string
    {
        if ($this->serverBasePath === null) {
            $this->serverBasePath = implode('/', array_slice(explode('/', $_SERVER['SCRIPT_NAME']), 0, -1)) . '/';
        }
        return $this->serverBasePath;
    }
}
