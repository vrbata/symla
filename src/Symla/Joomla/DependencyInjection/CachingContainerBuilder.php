<?php

namespace Symla\Joomla\DependencyInjection;

use Symfony\Component\Config\ConfigCache;
use Symfony\Component\DependencyInjection\ContainerBuilder as SymfonyContainerBuilder;
use Symfony\Component\DependencyInjection\Dumper\PhpDumper;
use Symla\Joomla\Environment\Environment;

class CachingContainerBuilder implements ContainerBuilder
{
    const CACHE_DIRECTORY = JPATH_ROOT . '/cache';

    const CONTAINER_BASE_CLASS = 'Container';

    /** @var  ContainerBuilder */
    private $containerBuilder;

    /** @var \Symla\Joomla\Environment\Environment */
    private $environment;

    public function __construct(ContainerBuilder $containerBuilder, Environment $environment)
    {
        $this->containerBuilder = $containerBuilder;
        $this->environment      = $environment;
    }

    /**
     * @return \Symfony\Component\DependencyInjection\ContainerBuilder
     */
    public function build()
    {
        $class = $this->containerClass();
        $path  = self::CACHE_DIRECTORY . '/' . $class . '.php';
        $cache = new ConfigCache($path, $this->environment->environment() !== Environment::DEVELOPMENT);

        if (!$cache->isFresh()) {
            $container = $this->containerBuilder->build();
            $this->dumpContainer($cache, $container, $this->containerClass(), self::CONTAINER_BASE_CLASS);

        }

        require_once $path;

        return new $class();
    }

    private function dumpContainer(ConfigCache $cache, SymfonyContainerBuilder $container, $class, $baseClass)
    {
        $dumper = new PhpDumper($container);

        $content = $dumper->dump(['class' => $class, 'base_class' => $baseClass]);
        if ($this->environment->environment() === Environment::PRODUCTION) {
            $content = $this->stripComments($content);
        }

        $cache->write($content, $container->getResources());
    }

    private function containerClass()
    {
        return ucfirst($this->environment->environment()) . 'ProjectContainer';
    }

    private function stripComments($source)
    {
        if (!function_exists('token_get_all')) {
            return $source;
        }

        $rawChunk    = '';
        $output      = '';
        $tokens      = token_get_all($source);
        $ignoreSpace = false;
        for (reset($tokens); false !== $token = current($tokens); next($tokens)) {
            if (is_string($token)) {
                $rawChunk .= $token;
            } elseif (T_START_HEREDOC === $token[0]) {
                $output .= $rawChunk . $token[1];
                do {
                    $token = next($tokens);
                    $output .= $token[1];
                } while ($token[0] !== T_END_HEREDOC);
                $rawChunk = '';
            } elseif (T_WHITESPACE === $token[0]) {
                if ($ignoreSpace) {
                    $ignoreSpace = false;

                    continue;
                }

                // replace multiple new lines with a single newline
                $rawChunk .= preg_replace(['/\n{2,}/S'], "\n", $token[1]);
            } elseif (in_array($token[0], [T_COMMENT, T_DOC_COMMENT])) {
                $ignoreSpace = true;
            } else {
                $rawChunk .= $token[1];

                // The PHP-open tag already has a new-line
                if (T_OPEN_TAG === $token[0]) {
                    $ignoreSpace = true;
                }
            }
        }

        $output .= $rawChunk;

        return $output;
    }
}