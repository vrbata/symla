<?php

namespace Symla\Joomla\Environment;

class Loader
{
    public function load(string $filePath)
    {
        if (!file_exists($filePath)) {
            throw new \InvalidArgumentException("File {$filePath} does not exists");
        }

        $properties = parse_ini_file($filePath);

        if (!array_key_exists('environment', $properties)) {
            throw new \InvalidArgumentException("Environment is not specified in {$filePath}");
        }

        return $properties['environment'];
    }
}