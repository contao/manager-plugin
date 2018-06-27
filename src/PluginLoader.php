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
 * This is a stub class which will be replaced during `composer install` or
 * `composer update` unless Composer is run with the `--no-scripts` flag.
 */
class PluginLoader
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

    /**
     * @param string|null $installedJson
     * @param array       $plugins
     */
    public function __construct(string $installedJson = null, array $plugins = null)
    {
        if (null !== $installedJson) {
            @trigger_error('Argument $installedJson is no longer supported in PluginLoader v2.3', E_USER_DEPRECATED);
        }

        $this->plugins = $plugins ?: [];
    }

    /**
     * Returns all active plugin instances.
     *
     * @return array
     */
    public function getInstances(): array
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
    public function getInstancesOf(string $type, bool $reverseOrder = false): array
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
    public function getDisabledPackages(): array
    {
        return $this->disabled;
    }

    /**
     * Sets the list of disabled Composer packages.
     *
     * @param array $packages
     */
    public function setDisabledPackages(array $packages): void
    {
        $this->disabled = $packages;
    }
}
