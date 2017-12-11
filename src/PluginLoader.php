<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\ManagerPlugin;

class PluginLoader
{
    const BUNDLE_PLUGINS = 'Contao\ManagerPlugin\Bundle\BundlePluginInterface';
    const CONFIG_PLUGINS = 'Contao\ManagerPlugin\Config\ConfigPluginInterface';
    const EXTENSION_PLUGINS = 'Contao\ManagerPlugin\Config\ExtensionPluginInterface';
    const ROUTING_PLUGINS = 'Contao\ManagerPlugin\Routing\RoutingPluginInterface';

    /**
     * @var array
     */
    private $plugins = [];

    /**
     * @var array
     */
    private $disabled = [];

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

        $plugins = $reverseOrder ? array_reverse($plugins, true) : $plugins;

        return array_diff_key($plugins, array_flip($this->disabled));
    }
}
