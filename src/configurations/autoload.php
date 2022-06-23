<?php
require_once '../../vendor/autoload.php'; //������������� Composer

spl_autoload_register(function($className): bool { //������������� �������
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
        throw new Exception('�� ������ ��������� ' . $className);
    }

    return true;
});