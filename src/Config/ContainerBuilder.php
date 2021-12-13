<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerPlugin\Config;

use Contao\ManagerPlugin\PluginLoader;
use Symfony\Component\DependencyInjection\ContainerBuilder as SymfonyContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ContainerBuilder extends SymfonyContainerBuilder
{
    /**
     * @var PluginLoader
     */
    private $pluginLoader;

    /**
     * @var array
     */
    private $managerConfig;

    public function __construct(PluginLoader $pluginLoader, array $managerConfig, ParameterBagInterface $parameterBag = null)
    {
        parent::__construct($parameterBag);

        $this->pluginLoader = $pluginLoader;
        $this->managerConfig = $managerConfig;
    }

    /**
     * @return array<string,mixed>
     */
    public function getManagerConfig(): array
    {
        return $this->managerConfig;
    }

    /**
     * {@inheritdoc}
     */
    public function getExtensionConfig($name): array
    {
        $configs = parent::getExtensionConfig($name);
        $plugins = $this->pluginLoader->getInstancesOf(PluginLoader::EXTENSION_PLUGINS);

        /** @var array<ExtensionPluginInterface> $plugins */
        foreach ($plugins as $plugin) {
            $configs = $plugin->getExtensionConfig($name, $configs, $this);
        }

        return $configs;
    }
}
