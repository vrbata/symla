<?php

namespace Symla\Joomla;

use Symla\Joomla\DependencyInjection\CachingContainerBuilder;
use Symla\Joomla\DependencyInjection\MemoryContainerBuilder;
use Symla\Joomla\Environment\Environment;

class Application
{
    /**
     * @var \Symla\Joomla\Environment\Environment
     */
    protected $environment;

    protected $initialized = false;

    /**
     * @type \Symla\Joomla\Application
     */
    protected static $instance = null;

    protected $container;

    private function __construct(Environment $environment)
    {
        $this->environment = $environment;
    }

    public static function instantiate(Environment $environment)
    {
        self::$instance = new self($environment);
    }

    /**
     * @return \Symla\Joomla\Application
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            throw new \RuntimeException("Application is not instantiated");
        }

        return self::$instance;
    }

    public function environment()
    {
        return $this->environment;
    }

    public function container()
    {
        if (!$this->initialized) {
            $this->initialize();
        }

        return $this->container;
    }

    protected function initialize()
    {
        $this->buildContainer();

        $this->initialized = true;
    }

    protected function buildContainer()
    {
        $builder = new CachingContainerBuilder(new MemoryContainerBuilder($this->environment()), $this->environment());

        $this->container = $builder->build();
    }
}
