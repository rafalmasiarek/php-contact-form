<?php

declare(strict_types=1);

$root = dirname(__DIR__);
foreach ([$root . '/vendor/autoload.php', $root . '/autoload.php'] as $file) {
    if (is_file($file)) {
        require $file;
        return;
    }
}
throw new RuntimeException('Autoloader not found in project root.');
