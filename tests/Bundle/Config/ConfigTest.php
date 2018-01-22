<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2018 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\ManagerPlugin\Tests\Bundle\Config;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\CoreBundle\HttpKernel\Bundle\ContaoModuleBundle;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Config\ConfigInterface;
use Contao\ManagerPlugin\Bundle\Config\ModuleConfig;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\KernelInterface;

class ConfigTest extends TestCase
{
    public function testCanBeInstantiated()
    {
        $config = new BundleConfig('foobar');

        $this->assertInstanceOf('Contao\ManagerPlugin\Bundle\Config\BundleConfig', $config);
        $this->assertInstanceOf('Contao\ManagerPlugin\Bundle\Config\ConfigInterface', $config);
    }

    public function testCreatesABundleConfigObject()
    {
        $config = BundleConfig::create('foobar');

        $this->assertInstanceOf(BundleConfig::class, $config);
        $this->assertInstanceOf(ConfigInterface::class, $config);
    }

    public function testReadsAndWritesTheName()
    {
        $config = new BundleConfig('foobar');

        $this->assertSame('foobar', $config->getName());
    }

    public function testReadsAndWritesTheReplaces()
    {
        $config = new BundleConfig('foobar');

        $this->assertEmpty($config->getReplace());

        $config->setReplace(['foobar']);

        $this->assertSame(['foobar'], $config->getReplace());
    }

    public function testReadsAndWritesTheLoadAfter()
    {
        $config = new BundleConfig('foobar');

        $this->assertEmpty($config->getLoadAfter());

        $config->setLoadAfter(['foobar']);

        $this->assertSame(['foobar'], $config->getLoadAfter());
    }

    public function testDisablesLoadingInProduction()
    {
        $config = new BundleConfig('foobar');

        $this->assertTrue($config->loadInDevelopment());
        $this->assertTrue($config->loadInProduction());

        $config->setLoadInProduction(false);

        $this->assertTrue($config->loadInDevelopment());
        $this->assertFalse($config->loadInProduction());
    }

    public function testDisablesLoadingInDevelopment()
    {
        $config = new BundleConfig('foobar');

        $this->assertTrue($config->loadInDevelopment());
        $this->assertTrue($config->loadInProduction());

        $config->setLoadInDevelopment(false);

        $this->assertFalse($config->loadInDevelopment());
        $this->assertTrue($config->loadInProduction());
    }

    public function testReturnsTheBundleInstances()
    {
        $config = BundleConfig::create(ContaoCoreBundle::class);
        $bundle = $config->getBundleInstance($this->createMock(KernelInterface::class));

        $this->assertInstanceOf(ContaoCoreBundle::class, $bundle);
    }

    public function testReturnsTheBundlePath()
    {
        $kernel = $this->createMock(KernelInterface::class);

        $kernel
            ->method('getRootDir')
            ->willReturn(__DIR__.'/../../Fixtures/Bundle/Config/app')
        ;

        $config = ModuleConfig::create('foobar');
        $bundle = $config->getBundleInstance($kernel);

        $this->assertInstanceOf(ContaoModuleBundle::class, $bundle);
        $this->assertSame(__DIR__.'/../../Fixtures/Bundle/Config/system/modules/foobar', $bundle->getPath());
    }

    public function testFailsToReturnTheBundleInstanceIfTheNameIsInvalid()
    {
        $kernel = $this->createMock(KernelInterface::class);

        $kernel
            ->method('getRootDir')
            ->willReturn(__DIR__.'/../../Fixtures/Bundle/Config/app')
        ;

        $config = ModuleConfig::create('barfoo');

        $this->expectException('LogicException');

        $config->getBundleInstance($kernel);
    }

    public function testLoadsTheModuleConfigurationAfterTheLegacyModules()
    {
        $config = ModuleConfig::create('foobar');

        $this->assertContains('core', $config->getLoadAfter());
        $this->assertNotContains('foobar', $config->getLoadAfter());
        $this->assertNotContains('news', $config->getLoadAfter());

        $config = ModuleConfig::create('a_module');

        $this->assertContains('core', $config->getLoadAfter());
        $this->assertNotContains('calendar', $config->getLoadAfter());

        $config = ModuleConfig::create('z_custom');

        $this->assertContains('core', $config->getLoadAfter());
        $this->assertContains('news', $config->getLoadAfter());
    }
}
