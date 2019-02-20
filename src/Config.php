<?php

namespace Asdfx\Tight;

class Config
{
    public static function get($configuration)
    {
        global $app;
        list($file, $key) = explode('.', $configuration, 2);
        $data = (require $app->getInstance('path.config') . DIRECTORY_SEPARATOR . $file . '.php');

        if (array_key_exists($key, $data)) {
            return $data[$key];
        }
    }
}