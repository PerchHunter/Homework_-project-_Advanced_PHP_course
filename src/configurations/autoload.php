<?php
require_once '../../vendor/autoload.php'; //автозагрузчик Composer

spl_autoload_register(function($className): bool { //автозагрузчик классов
    $dirs = ["../model/", "../controller/", "../lib/", "../tests"];
    $file = $className . ".php";
    $found = false;

    foreach ($dirs as $dir) {
        $path = $dir . $file;

        if (is_file($path)) {
            require_once($path);
            $found = true;
        }
    }

    if (!$found) {
        throw new Exception('Не удаётся загрузить ' . $className);
    }

    return true;
});