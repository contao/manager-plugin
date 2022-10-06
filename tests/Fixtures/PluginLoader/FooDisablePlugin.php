<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Foo\Disabled;

use Contao\ManagerPlugin\DisablePluginInterface;

class FooDisablePlugin implements DisablePluginInterface
{
    /**
     * @var bool
     */
    private $disabled;

    public function __construct(bool $disabled = false)
    {
        $this->disabled = $disabled;
    }

    public function isDisabled(): bool
    {
        return $this->disabled;
    }
}
