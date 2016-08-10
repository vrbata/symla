<?php

namespace Symla\Joomla\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Loader\DelegatingLoader;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Config\Loader\LoaderResolver;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerBuilder as SymfonyContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\ClosureLoader;
use Symfony\Component\DependencyInjection\Loader\IniFileLoader;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\MergeExtensionConfigurationPass;
use Symla\Joomla\Environment\Environment;

class MemoryContainerBuilder implements ContainerBuilder
{
    /** @type \Symla\Joomla\Environment\Environment */
    private $environment;

    private $components = [];

    public function __construct(Environment $environment)
    {
        $this->environment = $environment;
    }

    public function registerDynamicServices(Container $container)
    {
        $this->initComponents();

        foreach ($this->components as $component) {
            $extension = $component->getContainerExtension();
            if (method_exists($extension, 'registerDynamicServices')) {
                $extension->registerDynamicServices($container);
            }
        }
    }

    /**
     * @return \Symfony\Component\DependencyInjection\ContainerBuilder
     */
    public function build()
    {
        $this->initComponents();

        $container  = new \Symfony\Component\DependencyInjection\ContainerBuilder();
        $extensions = [];

        foreach ($this->components as $component) {
            if ($extension = $component->getContainerExtension()) {
                $container->registerExtension($extension);
                $extensions[] = $extension->getAlias();
            }
        }

        foreach ($this->components as $component) {
            $component->build($container);
        }

        $container->getCompilerPassConfig()->setMergePass(new MergeExtensionConfigurationPass($extensions));

        $this->registerContainerConfiguration($this->getContainerLoader($container));
        $this->registerDynamicServices($container);
        $container->compile();

        return $container;
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $configuration = JPATH_ROOT . '/config/config_' . $this->environment->environment() . '.yml';

        $loader->load($configuration);
    }

    private function initComponents()
    {
        if (count($this->components)) {
            return;
        }

        require_once JPATH_ROOT . '/administrator/components/com_config/helper/component.php';

        $componentsNames  = \ConfigHelperComponent::getAllComponents();
        $this->components = [];

        foreach ($componentsNames as $componentName) {
            $name = str_replace('com_', '', $componentName);
            $name = ucfirst($name);

            if (file_exists(JPATH_ROOT . '/administrator/components/' . $componentName . '/' . $name . 'Component.php')) {
                require_once JPATH_ROOT . '/administrator/components/' . $componentName . '/' . $name . 'Component.php';

                $class = $name . 'Component';

                $this->components[] = new $class();
            }
        }
    }

    private function getContainerLoader(SymfonyContainerBuilder $container)
    {
        $locator  = new FileLocator();
        $resolver = new LoaderResolver([
            new XmlFileLoader($container, $locator),
            new YamlFileLoader($container, $locator),
            new IniFileLoader($container, $locator),
            new PhpFileLoader($container, $locator),
            new ClosureLoader($container),
        ]);

        return new DelegatingLoader($resolver);
    }
}