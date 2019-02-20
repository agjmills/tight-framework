<?php

if (function_exists('view') === false) {
    function view(string $template, array $parameters = [])
    {
        $loader = new \Twig\Loader\FilesystemLoader('../' . config('views.path'));
        $twig = new \Twig\Environment($loader, [
            'cache' => '../' . config('views.cache'),
            'auto_reload' => true,
        ]);
        try {
            return $twig->render($template, $parameters);
        } catch (Twig_Error_Loader $exception) {
            return 'Unable to find template: ' . $template;
        }
    }
}

if (function_exists('config') === false) {
    function config($config) {
        return \Asdfx\Tight\Config::get($config);
    }
}

if (function_exists('dd') === false) {
    function dd($var) {
        dump($var);
        die();
    }
}

if (! function_exists('app')) {
    function app()
    {
        return Asdfx\Tight\Tight::getInstance();
    }
}
