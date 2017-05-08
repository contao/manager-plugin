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
interface FirewallPluginInterface
{
    /**
     * Passes the firewall config as array and expects the adjusted configuration.
     *
     * @param array $config
     *
     * @return array
     */
    public function getFirewallConfig(array $config);
}
