<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerPlugin\Composer;

use Composer\Package\Locker;
use Composer\Package\RootPackageInterface;
use Contao\ManagerPlugin\Dependency\DependencyResolverTrait;
use Contao\ManagerPlugin\Dependency\DependentPluginInterface;

class Installer
{
    use DependencyResolverTrait;

    /**
     * @var string
     */
    private static $generatedClassTemplate = <<<'PHP'
<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-%s Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\ManagerPlugin;

use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Config\ConfigPluginInterface;
use Contao\ManagerPlugin\Config\ExtensionPluginInterface;
use Contao\ManagerPlugin\Routing\RoutingPluginInterface;

/**
 * This class has been auto-generated. It will be overwritten at every run of
 * `composer install` or `composer update`.
 * 
 * @see \Contao\ManagerBundle\Composer\Installer
 */
%s
{
    const BUNDLE_PLUGINS = BundlePluginInterface::class;
    const CONFIG_PLUGINS = ConfigPluginInterface::class;
    const EXTENSION_PLUGINS = ExtensionPluginInterface::class;
    const ROUTING_PLUGINS = RoutingPluginInterface::class;

    /**
     * @var array
     */
    private $plugins;

    public function __construct()
    {
        $this->plugins = %s;
    }

    /**
     * Returns all active plugin instances.
     *
     * @return array
     */
    public function getInstances()
    {
        return $this->plugins;
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

        if ($reverseOrder) {
            $plugins = array_reverse($plugins, true);
        }

        return $plugins;
    }
}

PHP;

    /**
     * Sets the Contao Manager plugins.
     *
     * @param Locker               $locker
     * @param RootPackageInterface $rootPackage
     *
     * @throws \RuntimeException
     */
    public function dumpPlugins(Locker $locker, RootPackageInterface $rootPackage): void
    {
        $plugins = [];
        $disabled = $this->getDisabledPackages($rootPackage);
        $lockData = $locker->getLockData();

        if (!isset($lockData['packages-dev'])) {
            $lockData['packages-dev'] = [];
        }

        foreach (array_merge($lockData['packages'], $lockData['packages-dev']) as $package) {
            if (\in_array($package['name'], $disabled, true)) {
                continue;
            }

            if (isset($package['extra']['contao-manager-plugin'])) {
                if (!class_exists($package['extra']['contao-manager-plugin'])) {
                    throw new \RuntimeException(
                        sprintf('Plugin class "%s" not found', $package['extra']['contao-manager-plugin'])
                    );
                }

                $plugins[$package['name']] = new $package['extra']['contao-manager-plugin']();
            }
        }

        $plugins = $this->orderPlugins($plugins);

        // Instantiate a global plugin to load AppBundle or other customizations
        $appPlugin = '\ContaoManagerPlugin';

        if (class_exists($appPlugin)) {
            $plugins['app'] = new $appPlugin();
        }

        $this->dumpClass($plugins);
    }

    /**
     * Finds disabled packages in the root package extras.
     *
     * @param RootPackageInterface $rootPackage
     *
     * @return array
     */
    private function getDisabledPackages(RootPackageInterface $rootPackage)
    {
        $extra = $rootPackage->getExtra();

        if (!isset($extra['contao-manager']['disabled-packages'])) {
            return [];
        }

        return (array) $extra['contao-manager']['disabled-packages'];
    }

    /**
     * Dumps the PluginLoader class.
     */
    private function dumpClass(array $plugins): void
    {
        $content = sprintf(
            static::$generatedClassTemplate,
            date('Y'),
            'cla'.'ss '.'PluginLoader', // note: workaround for regex-based code parsers :-(
            'unserialize('.var_export(serialize($plugins), true).')'
        );

        file_put_contents(__DIR__.'/../PluginLoader.php', $content);
    }

    /**
     * Orders the plugins.
     *
     * @param array $plugins
     *
     * @return array
     */
    private function orderPlugins(array $plugins)
    {
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
}
