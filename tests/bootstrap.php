<?php
/*
 * Require the composer autoloader or create PSR-4 autoloaders for eftec\bladeone\ and eftec\tests\ found in composer.json.
 */


    function create_autoloader($prefix, $base_dir) {
        return function ($class) use ($prefix, $base_dir) {
            if (strncmp($prefix, $class, strlen($prefix)) !== 0) {
                return;
            }

            $file = $base_dir . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';

            if (file_exists($file)) {
                require $file;
            }
        };
    }

    spl_autoload_register(create_autoloader("eftec\\statemachineone\\", __DIR__ . '/../lib/'));
    spl_autoload_register(create_autoloader("eftec\\tests\\", __DIR__ . '/'));

