<?php

namespace App\helper;

class DotEnvEnvironment
{
    public function load($path): void
    {
        $lines = file($path . '/.env');
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || $line[0] == '#') {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);

            putenv(sprintf('%s=%s', $key, $value));
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }
}