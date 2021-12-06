<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerPlugin\Bundle;

use Contao\ManagerPlugin\Bundle\Config\ConfigInterface;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;

interface BundlePluginInterface
{
    /**
     * Gets a list of autoload configurations for this bundle.
     *
     * @return array<ConfigInterface>
     */
    public function getBundles(ParserInterface $parser);
}
