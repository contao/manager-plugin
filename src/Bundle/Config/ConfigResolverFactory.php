<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
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
