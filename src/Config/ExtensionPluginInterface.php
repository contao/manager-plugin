<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\ManagerPlugin\Config;

/**
 * @author Andreas Schempp <https://github.com/aschempp>
 */
interface ExtensionPluginInterface
{
    /**
     * Allows a plugin to override extension configuration.
     *
     * @param string           $extensionName
     * @param array            $extensionConfigs
     * @param ContainerBuilder $container
     *
     * @return array
     */
    public function getExtensionConfig($extensionName, array $extensionConfigs, ContainerBuilder $container);
}
