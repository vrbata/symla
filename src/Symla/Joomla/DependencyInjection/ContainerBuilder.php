<?php

namespace Symla\Joomla\DependencyInjection;

interface ContainerBuilder
{
    /**
     * @return \Symfony\Component\DependencyInjection\ContainerBuilder
     */

    public function build();
}