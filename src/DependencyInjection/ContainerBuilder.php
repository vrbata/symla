<?php

namespace Symla\Joomla\DependencyInjection;

use Symfony\Component\DependencyInjection\Container;

interface ContainerBuilder
{
    /**
     * @return \Symfony\Component\DependencyInjection\ContainerBuilder
     */

    public function build();

    public function registerDynamicServices(Container $container);
}