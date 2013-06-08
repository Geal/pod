<?php
define('POD_DIR', dirname(__DIR__).'/src');

spl_autoload_register(function ($class) {
    if (0 === strpos($class, 'POD\\')) {
        $class = str_replace('\\', '/', $class);
        $file = sprintf("%s/%s.php", POD_DIR, $class);
        printf("Auto loading class '%s' (%s).\n", $class, $file);

        require $file;
    }
});
