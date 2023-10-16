<?php

namespace Ptapas;

defined('ABSPATH') || exit;

class Plugin
{
    public static function init(): void
    {
        $directories = [
            'Features',
            'Compatibility'
        ];

        foreach ($directories as $directory) {
            self::createInstances($directory);
        }
    }

    private static function createInstances(string $directory): array
    {
        $baseFolder = __DIR__;
        $baseNamespace = __NAMESPACE__;

        $folderPath = $baseFolder . '/' . $directory;
        $namespace = $baseNamespace . '\\' . $directory;

        $instances = [];
        $files = glob($folderPath . '/*.php');

        foreach ($files as $filename) {
            $className = $namespace . '\\' . basename($filename, '.php');

            if (class_exists($className)) {
                $instances[] = new $className();
            } else {
                error_log('Class ' . $className . ' does not exist.');
            }
        }

        return $instances;
    }
}
