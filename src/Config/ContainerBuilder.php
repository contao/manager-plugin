<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\ManagerPlugin\Config;

use Contao\ManagerPlugin\PluginLoader;
use Symfony\Component\DependencyInjection\ContainerBuilder as BaseContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ContainerBuilder extends BaseContainerBuilder
{
    /**
     * @var PluginLoader
     */
    private $pluginLoader;

    /**
     * @var array
     */
    private $managerConfig;

    /**
     * @param PluginLoader               $pluginLoader
     * @param array                      $managerConfig
     * @param ParameterBagInterface|null $parameterBag
     */
    public function __construct(PluginLoader $pluginLoader, array $managerConfig, ParameterBagInterface $parameterBag = null)
    {
        parent::__construct($parameterBag);

        $this->pluginLoader = $pluginLoader;
        $this->managerConfig = $managerConfig;
    }

    /**
     * Gets the manager configuration.
     *
     * @return array
     */
    public function getManagerConfig()
    {
        return $this->managerConfig;
    }

    /**
     * {@inheritdoc}
     */
    public function getExtensionConfig($name)
    {
        $configs = parent::getExtensionConfig($name);
        $plugins = $this->pluginLoader->getInstancesOf(PluginLoader::EXTENSION_PLUGINS);

        /** @var ExtensionPluginInterface[] $plugins */
        foreach ($plugins as $plugin) {
            $configs = $plugin->getExtensionConfig($name, $configs, $this);
        }

        return $configs;
    }
}
