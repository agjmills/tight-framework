<?php

if (function_exists('view') === false) {
    function view(string $template, array $parameters = [])
    {
        $loader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/../resources/views');
        $twig = new \Twig\Environment($loader, [
            'cache' => __DIR__ . '/../storage/cache/views',
            'auto_reload' => true,
        ]);
        try {
            return $twig->render($template, $parameters);
        } catch (Twig_Error_Loader $exception) {
            return 'Unable to find template: ' . $template;
        }
    }
}


if (function_exists('dd') === false) {
    function dd($var) {
        dump($var);
        die();
    }
}