<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Foo\Dependend;

use Contao\ManagerPlugin\Dependency\DependentPluginInterface;

class FooDependendPlugin implements DependentPluginInterface
{
    /**
     * Gets list of Composer packages names that must be loaded before this plugin.
     *
     * @return array<string>
     */
    public function getPackageDependencies()
    {
        return ['foo/bar-bundle'];
    }
}
