<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Foo\Console;

use Contao\ManagerPlugin\Config\ConfigPluginInterface;
use Symfony\Component\Config\Loader\LoaderInterface;

class FooConsolePlugin implements ConfigPluginInterface
{
    public function registerContainerConfiguration(LoaderInterface $loader, array $managerConfig): void
    {
    }
}
