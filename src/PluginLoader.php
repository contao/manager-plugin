<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\ManagerPlugin;

use Contao\ManagerPlugin\Dependency\DependencyResolverTrait;
use Contao\ManagerPlugin\Dependency\DependentPluginInterface;
use Contao\ManagerPlugin\Dependency\UnresolvableDependenciesException;

class PluginLoader
{
    use DependencyResolverTrait;

    const BUNDLE_PLUGINS = 'Contao\ManagerPlugin\Bundle\BundlePluginInterface';
    const CONFIG_PLUGINS = 'Contao\ManagerPlugin\Config\ConfigPluginInterface';
    const EXTENSION_PLUGINS = 'Contao\ManagerPlugin\Config\ExtensionPluginInterface';
    const ROUTING_PLUGINS = 'Contao\ManagerPlugin\Routing\RoutingPluginInterface';

    /**
     * @var string
     */
    private $installedJson;

    /**
     * @var array
     */
    private $plugins;

    /**
     * @var array
     */
    private $disabled = [];

    /**
     * @param string $installedJson
     */
    public function __construct($installedJson)
    {
        $this->installedJson = $installedJson;
    }

    /**
     * Returns all active plugin instances.
     *
     * @return array
     */
    public function getInstances()
    {
        $this->load();

        return array_diff_key($this->plugins, $this->disabled);
    }

    /**
     * Returns the active plugin instances of a given type (see class constants).
     *
     * @param string $type
     * @param bool   $reverseOrder
     *
     * @return array
     */
    public function getInstancesOf($type, $reverseOrder = false)
    {
        $plugins = array_filter(
            $this->getInstances(),
            function ($plugin) use ($type) {
                return is_a($plugin, $type);
            }
        );

        $plugins = $reverseOrder ? array_reverse($plugins, true) : $plugins;

        return array_diff_key($plugins, $this->disabled);
    }

    /**
     * Gets the list of disabled Composer packages.
     *
     * @return array
     */
    public function getDisabledPackages()
    {
        return array_values(array_flip($this->disabled));
    }

    /**
     * Sets the list of disabled Composer packages.
     *
     * @param array $plugins
     */
    public function setDisabledPackages(array $plugins)
    {
        $this->disabled = array_flip(array_values($plugins));
    }

    /**
     * Orders the plugins.
     *
     * @param array $plugins
     *
     * @throws UnresolvableDependenciesException
     *
     * @return array
     */
    protected function orderPlugins(array $plugins)
    {
        $this->plugins = [];

        $ordered = [];
        $dependencies = [];
        $packages = array_keys($plugins);

        // Load the manager bundle first
        if (isset($plugins['contao/manager-bundle'])) {
            array_unshift($packages, 'contao/manager-bundle');
            $packages = array_unique($packages);
        }

        // Walk through the packages
        foreach ($packages as $packageName) {
            $dependencies[$packageName] = [];

            if ($plugins[$packageName] instanceof DependentPluginInterface) {
                $dependencies[$packageName] = $plugins[$packageName]->getPackageDependencies();
            }
        }

        foreach ($this->orderByDependencies($dependencies) as $packageName) {
            $ordered[$packageName] = $plugins[$packageName];
        }

        return $ordered;
    }

    /**
     * Loads the plugins from the Composer installed.json file.
     *
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     * @throws UnresolvableDependenciesException
     */
    private function load()
    {
        if (null !== $this->plugins) {
            return;
        }

        if (!is_file($this->installedJson)) {
            throw new \InvalidArgumentException(
                sprintf('Composer installed.json was not found at "%s"', $this->installedJson)
            );
        }

        $plugins = [];
        $json = json_decode(file_get_contents($this->installedJson), true);

        if (null === $json) {
            throw new \RuntimeException(sprintf('File "%s" cannot be decoded', $this->installedJson));
        }

        foreach ($json as $package) {
            if (isset($package['extra']['contao-manager-plugin'])) {
                if (!class_exists($package['extra']['contao-manager-plugin'])) {
                    throw new \RuntimeException(
                        sprintf('Plugin class "%s" not found', $package['extra']['contao-manager-plugin'])
                    );
                }

                $plugins[$package['name']] = new $package['extra']['contao-manager-plugin']();
            }
        }

        $this->plugins = $this->orderPlugins($plugins);

        // Instantiate a global plugin to load AppBundle or other customizations
        $appPlugin = '\ContaoManagerPlugin';

        if (class_exists($appPlugin)) {
            $this->plugins['app'] = new $appPlugin();
        }
    }
}
