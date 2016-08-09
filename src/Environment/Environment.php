<?php

namespace Symla\Joomla\Environment;

class Environment
{
    const DEVELOPMENT = 'dev';

    const PRODUCTION = 'prod';

    private $environment;

    public function __construct(string $environment)
    {
        if ($environment !== self::DEVELOPMENT && $environment !== self::PRODUCTION) {
            throw new \InvalidArgumentException("Unsupported environment {$environment}");
        }

        $this->environment = $environment;
    }

    /**
     * @return string
     */
    public function environment()
    {
        return $this->environment;
    }
}