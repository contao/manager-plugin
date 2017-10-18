<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\ManagerPlugin\Bundle\Config;

/**
 * Factory for ConfigResolverInterface.
 */
class ConfigResolverFactory
{
    /**
     * Creates an instance of ConfigResolverInterface.
     *
     * @return ConfigResolverInterface
     */
    public function create()
    {
        return new ConfigResolver();
    }
}
