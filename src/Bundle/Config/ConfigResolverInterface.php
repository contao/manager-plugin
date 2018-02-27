<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerPlugin\Bundle\Config;

use Contao\ManagerPlugin\Dependency\UnresolvableDependenciesException;

interface ConfigResolverInterface
{
    /**
     * Adds a configuration object.
     *
     * @param ConfigInterface $config
     *
     * @return $this
     */
    public function add(ConfigInterface $config);

    /**
     * Returns an array of bundle configs for development or production.
     *
     * @param bool $development
     *
     * @throws UnresolvableDependenciesException
     *
     * @return BundleConfig[]
     */
    public function getBundleConfigs($development);
}
