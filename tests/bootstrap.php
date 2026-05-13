<?php

declare(strict_types=1);

$autoloaders = [
    __DIR__.'/../vendor/autoload.php',
    __DIR__.'/../../../../vendor/autoload.php',
];

foreach ($autoloaders as $autoload) {
    if (is_file($autoload)) {
        require_once $autoload;

        break;
    }
}

$prefixes = [
    'IvanBaric\\Seo\\Tests\\' => __DIR__,
    'IvanBaric\\Seo\\' => __DIR__.'/../src',
    'IvanBaric\\Corexis\\' => __DIR__.'/../../corexis/src',
];

spl_autoload_register(static function (string $class) use ($prefixes): void {
    foreach ($prefixes as $prefix => $basePath) {
        if (! str_starts_with($class, $prefix)) {
            continue;
        }

        $relative = substr($class, strlen($prefix));
        $path = $basePath.DIRECTORY_SEPARATOR.str_replace('\\', DIRECTORY_SEPARATOR, $relative).'.php';

        if (is_file($path)) {
            require_once $path;
        }
    }
});

require_once __DIR__.'/../src/helpers.php';
require_once __DIR__.'/../../corexis/src/helpers.php';
