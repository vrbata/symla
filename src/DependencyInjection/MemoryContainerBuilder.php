<?php

namespace Symla\Joomla\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Loader\DelegatingLoader;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Config\Loader\LoaderResolver;
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
    /** @type \Symla\Joomla\Environment\Environment  */
    private $environment;

    public function __construct(Environment $environment)
    {
        $this->environment = $environment;
    }

    /**
     * @return \Symfony\Component\DependencyInjection\ContainerBuilder
     */
    public function build()
    {
        require_once JPATH_ROOT . '/administrator/components/com_config/helper/component.php';

        $componentsNames = \ConfigHelperComponent::getAllComponents();
        $container       = new \Symfony\Component\DependencyInjection\ContainerBuilder();
        $components      = [];

        foreach ($componentsNames as $componentName) {
            $name = str_replace('com_', '', $componentName);
            $name = ucfirst($name);

            if (file_exists(JPATH_ROOT . '/administrator/components/' . $componentName . '/' . $name . 'Component.php')) {
                require_once JPATH_ROOT . '/administrator/components/' . $componentName . '/' . $name . 'Component.php';

                $class = $name . 'Component';

                $components[] = new $class();
            }
        }

        $extensions = [];

        /** @type \SymlaComponent $component */
        foreach ($components as $component) {
            if ($extension = $component->getContainerExtension()) {
                $container->registerExtension($extension);
                $extensions[] = $extension->getAlias();
            }
        }

        foreach ($components as $component) {
            $component->build($container);
        }

        $container->getCompilerPassConfig()->setMergePass(new MergeExtensionConfigurationPass($extensions));

        $this->registerContainerConfiguration($this->getContainerLoader($container));

        $container->compile();

        return $container;
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $configuration = JPATH_ROOT . '/config/config_' . $this->environment->environment() . '.yml';

        $loader->load($configuration);
    }

    protected function getContainerLoader(SymfonyContainerBuilder $container)
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