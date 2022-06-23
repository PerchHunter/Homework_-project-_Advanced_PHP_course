<?php

class Config
{
    private static array $configCache = [];

    public static function get(string $parameter): string
    {
        if (!isset(self::getCurrentConfiguration()[$parameter])) {
            throw new Exception('Параметр ' . $parameter . ' не существует');
        }
        return self::getCurrentConfiguration()[$parameter];
    }

    private static function getCurrentConfiguration(): array
    {
        if (empty(self::$configCache)) {
            $configDir = __DIR__ . '/../configurations/';
            $configProd = $configDir . 'config.prod.php';
            $configDev = $configDir . 'config.dev.php';
            $configDefault = $configDir . 'config.default.php';

            if (is_file($configProd)) {
                require_once $configProd;
            } else if (is_file($configDev)) {
                require_once $configDev;
            } else if (is_file($configDefault)) {
                require_once $configDefault;
            } else {
                throw new Exception('Не найден файл конфигурации');
            }

            if (!isset($config) || !is_array($config)) {
                throw new Exception('Не удалось загрузить конфигурацию');
            }

            self::$configCache = $config;
        }
        return self::$configCache;
    }
}
