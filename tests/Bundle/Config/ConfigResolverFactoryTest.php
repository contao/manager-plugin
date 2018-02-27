<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerPlugin\Tests\Bundle\Config;

use Contao\ManagerPlugin\Bundle\Config\ConfigResolverFactory;
use Contao\ManagerPlugin\Bundle\Config\ConfigResolverInterface;
use PHPUnit\Framework\TestCase;

class ConfigResolverFactoryTest extends TestCase
{
    public function testCanBeInstantiated()
    {
        $factory = new ConfigResolverFactory();

        $this->assertInstanceOf('Contao\ManagerPlugin\Bundle\Config\ConfigResolverFactory', $factory);
    }

    public function testCreatesAConfigResolverObject()
    {
        $factory = new ConfigResolverFactory();
        $resolver = $factory->create();

        $this->assertInstanceOf(ConfigResolverInterface::class, $resolver);
    }
}
