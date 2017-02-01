<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

/**
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\ManagerPlugin\Tests\Bundle\Config;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\CoreBundle\HttpKernel\Bundle\ContaoModuleBundle;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Config\ConfigInterface;
use Contao\ManagerPlugin\Bundle\Config\ModuleConfig;
use Symfony\Component\HttpKernel\KernelInterface;

class ConfigTest extends \PHPUnit_Framework_TestCase
{
    const FIXTURES_DIR = '/../../Fixtures/Bundle/Config';

    public function testInstantiation()
    {
        $config = new BundleConfig('foobar');

        $this->assertInstanceOf('Contao\ManagerPlugin\Bundle\Config\BundleConfig', $config);
        $this->assertInstanceOf('Contao\ManagerPlugin\Bundle\Config\ConfigInterface', $config);
    }

    public function testStaticCreate()
    {
        $config = BundleConfig::create('foobar');

        $this->assertInstanceOf(BundleConfig::class, $config);
        $this->assertInstanceOf(ConfigInterface::class, $config);
    }

    public function testSetAndGetName()
    {
        $config = new BundleConfig('foobar');

        $this->assertSame('foobar', $config->getName());
    }

    public function testSetAndGetReplace()
    {
        $config = new BundleConfig('foobar');

        $this->assertEmpty($config->getReplace());

        $config->setReplace(['foobar']);

        $this->assertSame(['foobar'], $config->getReplace());
    }

    public function testSetAndGetLoadAfter()
    {
        $config = new BundleConfig('foobar');

        $this->assertEmpty($config->getLoadAfter());

        $config->setLoadAfter(['foobar']);

        $this->assertSame(['foobar'], $config->getLoadAfter());
    }

    public function testSetAndGetLoadInProduction()
    {
        $config = new BundleConfig('foobar');

        $this->assertTrue($config->loadInDevelopment());
        $this->assertTrue($config->loadInProduction());

        $config->setLoadInProduction(false);

        $this->assertTrue($config->loadInDevelopment());
        $this->assertFalse($config->loadInProduction());
    }

    public function testSetAndGetLoadInDevelopment()
    {
        $config = new BundleConfig('foobar');

        $this->assertTrue($config->loadInDevelopment());
        $this->assertTrue($config->loadInProduction());

        $config->setLoadInDevelopment(false);

        $this->assertFalse($config->loadInDevelopment());
        $this->assertTrue($config->loadInProduction());
    }

    public function testBundleConfigInstance()
    {
        $kernel = $this->getMock(KernelInterface::class);
        $config = BundleConfig::create(ContaoCoreBundle::class);

        $bundle = $config->getBundleInstance($kernel);

        $this->assertInstanceOf(ContaoCoreBundle::class, $bundle);
    }

    public function testModuleConfigExtendsBundleConfig()
    {
        $config = new ModuleConfig('foobar');

        $this->assertInstanceOf(ModuleConfig::class, $config);
        $this->assertInstanceOf(BundleConfig::class, $config);
        $this->assertInstanceOf(ConfigInterface::class, $config);
    }

    public function testModuleConfigInstance()
    {
        $kernel = $this->getMock(KernelInterface::class);
        $kernel
            ->method('getRootDir')
            ->willReturn(__DIR__.self::FIXTURES_DIR.'/app')
        ;

        $config = ModuleConfig::create('foobar');

        $bundle = $config->getBundleInstance($kernel);

        $this->assertInstanceOf(ContaoModuleBundle::class, $bundle);
        $this->assertEquals(
            __DIR__.self::FIXTURES_DIR.'/system/modules/foobar',
            $bundle->getPath()
        );
    }

    /**
     * @expectedException \LogicException
     */
    public function testModuleConfigInstanceWithInvalidNameThrowsException()
    {
        $kernel = $this->getMock(KernelInterface::class);
        $kernel
            ->method('getRootDir')
            ->willReturn(__DIR__.self::FIXTURES_DIR.'/app')
        ;

        $config = ModuleConfig::create('barfoo');

        $config->getBundleInstance($kernel);
    }

    public function testModuelConfigLoadsAfterLegacyModules()
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
