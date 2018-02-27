<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerPlugin\Bundle\Config;

class ConfigResolverFactory
{
    /**
     * Creates a ConfigResolver instance.
     *
     * @return ConfigResolverInterface
     */
    public function create()
    {
        return new ConfigResolver();
    }
}
