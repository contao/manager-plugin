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

interface ExtensionPluginInterface
{
    /**
     * Allows a plugin to override extension configuration.
     *
     * @param string                    $extensionName
     * @param list<array<string,mixed>> $extensionConfigs
     *
     * @return list<array<string,mixed>>
     */
    public function getExtensionConfig($extensionName, array $extensionConfigs, ContainerBuilder $container);
}
