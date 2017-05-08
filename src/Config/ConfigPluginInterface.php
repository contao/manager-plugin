<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\ManagerPlugin\Config;

use Symfony\Component\Config\Loader\LoaderInterface;

/**
 * @author Andreas Schempp <https://github.com/aschempp>
 */
interface ConfigPluginInterface
{
    /**
     * Allows a plugin to load the container configuration.
     *
     * @param LoaderInterface $loader
     * @param array           $managerConfig
     */
    public function registerContainerConfiguration(LoaderInterface $loader, array $managerConfig);
}
