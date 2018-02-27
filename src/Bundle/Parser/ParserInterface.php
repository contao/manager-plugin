<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerPlugin\Bundle\Parser;

use Contao\ManagerPlugin\Bundle\Config\ConfigInterface;

interface ParserInterface
{
    /**
     * Parses a configuration file.
     *
     * @param string      $resource
     * @param string|null $type
     *
     * @throws \Exception
     *
     * @return ConfigInterface[]
     */
    public function parse($resource, $type = null);

    /**
     * Returns true if the class supports the given config file.
     *
     * @param string      $resource
     * @param string|null $type
     */
    public function supports($resource, $type = null);
}
