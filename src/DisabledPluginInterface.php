<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerPlugin;

interface DisabledPluginInterface
{
    /**
     * Gets whether the plugin is disabled.
     */
    public function isDisabled(): bool;
}
