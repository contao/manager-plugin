<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerPlugin\Bundle;

use Contao\ManagerPlugin\Bundle\Config\ConfigInterface;
use Contao\ManagerPlugin\Bundle\Config\ConfigResolverFactory;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use Contao\ManagerPlugin\PluginLoader;
use Symfony\Component\Filesystem\Filesystem;

class BundleLoader
{
    /**
     * @var PluginLoader
     */
    private $pluginLoader;

    /**
     * @var ConfigResolverFactory
     */
    private $resolverFactory;

    /**
     * @var ParserInterface
     */
    private $parser;

    /**
     * @var Filesystem
     */
    private $filesystem;

    public function __construct(PluginLoader $pluginLoader, ConfigResolverFactory $resolverFactory, ParserInterface $parser, Filesystem $filesystem = null)
    {
        $this->pluginLoader = $pluginLoader;
        $this->resolverFactory = $resolverFactory;
        $this->parser = $parser;
        $this->filesystem = $filesystem;

        if (null === $this->filesystem) {
            $this->filesystem = new Filesystem();
        }
    }

    /**
     * Returns an ordered bundles map.
     *
     * @return array<ConfigInterface>
     */
    public function getBundleConfigs(bool $development, string $cacheFile = null): array
    {
        if (null !== $cacheFile) {
            return $this->loadFromCache($development, $cacheFile);
        }

        return $this->loadFromPlugins($development, $cacheFile);
    }

    /**
     * Loads the bundles map from cache.
     *
     * @return array<ConfigInterface>
     */
    private function loadFromCache(bool $development, string $cacheFile = null): array
    {
        $bundleConfigs = is_file($cacheFile) ? include $cacheFile : null;

        if (!\is_array($bundleConfigs) || 0 === \count($bundleConfigs)) {
            $bundleConfigs = $this->loadFromPlugins($development, $cacheFile);
        }

        return $bundleConfigs;
    }

    /**
     * Generates the bundles map.
     *
     * @return array<ConfigInterface>
     */
    private function loadFromPlugins(bool $development, string $cacheFile = null): array
    {
        $resolver = $this->resolverFactory->create();

        /** @var array<BundlePluginInterface> $plugins */
        $plugins = $this->pluginLoader->getInstancesOf(PluginLoader::BUNDLE_PLUGINS);

        foreach ($plugins as $plugin) {
            foreach ($plugin->getBundles($this->parser) as $config) {
                $resolver->add($config);
            }
        }

        $bundleConfigs = $resolver->getBundleConfigs($development);

        if (null !== $cacheFile) {
            $this->filesystem->dumpFile($cacheFile, sprintf('<?php return %s;', var_export($bundleConfigs, true)));
        }

        return $bundleConfigs;
    }
}
