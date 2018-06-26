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

use Composer\IO\IOInterface;
use Composer\Package\Locker;
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

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
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
 * @see \Contao\ManagerPlugin\Composer\Installer
 */
%s
{
    public const BUNDLE_PLUGINS = BundlePluginInterface::class;
    public const CONFIG_PLUGINS = ConfigPluginInterface::class;
    public const EXTENSION_PLUGINS = ExtensionPluginInterface::class;
    public const ROUTING_PLUGINS = RoutingPluginInterface::class;

    /**
     * @var array
     */
    private $plugins;

    /**
     * @var array
     */
    private $disabled = [];

    public function __construct(array $plugins = null)
    {
        $this->plugins = $plugins ?: %s;
    }

    /**
     * Returns all active plugin instances.
     *
     * @return array
     */
    public function getInstances()
    {
        return array_diff_key($this->plugins, array_flip($this->disabled));
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

        return array_diff_key($plugins, array_flip($this->disabled));
    }

    /**
     * Gets the list of disabled Composer packages.
     *
     * @return array
     */
    public function getDisabledPackages()
    {
        return $this->disabled;
    }

    /**
     * Sets the list of disabled Composer packages.
     *
     * @param array $packages
     */
    public function setDisabledPackages(array $packages)
    {
        $this->disabled = $packages;
    }
}

PHP;

    /**
     * Sets the Contao Manager plugins.
     *
     * @param Locker $locker
     *
     * @throws \RuntimeException
     */
    public function dumpPlugins(Locker $locker, IOInterface $io): void
    {
        $plugins = [];
        $lockData = $locker->getLockData();

        if (!isset($lockData['packages-dev'])) {
            $lockData['packages-dev'] = [];
        }

        foreach (array_merge($lockData['packages'], $lockData['packages-dev']) as $package) {
            if (isset($package['extra']['contao-manager-plugin'])) {
                if (!class_exists($package['extra']['contao-manager-plugin'])) {
                    throw new \RuntimeException(
                        sprintf('Plugin class "%s" not found', $package['extra']['contao-manager-plugin'])
                    );
                }

                $io->write(' - Added Contao Manager plugin for '.$package['name'], true, IOInterface::VERY_VERBOSE);

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
     * Dumps the PluginLoader class.
     */
    private function dumpClass(array $plugins): void
    {
        $load = [];

        foreach ($plugins as $package => $plugin) {
            $class = get_class($plugin);
            $load[] = "            '$package' => new \\$class()";
        }

        $content = sprintf(
            static::$generatedClassTemplate,
            'cla'.'ss '.'PluginLoader', // note: workaround for regex-based code parsers :-(
            sprintf("[\n%s\n        ]", implode(",\n", $load))
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
