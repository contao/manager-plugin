<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerPlugin\Dependency;

interface DependentPluginInterface
{
    /**
     * Gets list of Composer packages names that must be loaded before this plugin.
     *
     * @return array<string>
     */
    public function getPackageDependencies();
}
