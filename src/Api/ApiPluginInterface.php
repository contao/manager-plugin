<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerPlugin\Api;

interface ApiPluginInterface
{
    /**
     * Returns the features this plugin can handle.
     *
     * @return array<string, array<string>>
     */
    public function getApiFeatures(): array;

    /**
     * Adds additional commands to the Manager API.
     *
     * @return array<string>
     */
    public function getApiCommands(): array;
}
