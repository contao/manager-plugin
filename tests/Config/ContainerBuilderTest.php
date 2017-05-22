<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\ManagerPlugin\Tests\Config;

use Contao\ManagerPlugin\Config\ContainerBuilder;
use Contao\ManagerPlugin\Config\ExtensionPluginInterface;
use Contao\ManagerPlugin\PluginLoader;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;

class ContainerBuilderTest extends \PHPUnit_Framework_TestCase
{
    public function testInstantiation()
    {
        $container = new ContainerBuilder($this->mockPluginLoader($this->never()), []);

        $this->assertInstanceOf('Contao\ManagerPlugin\Config\ContainerBuilder', $container);
    }

    public function testGetManagerConfig()
    {
        $container = new ContainerBuilder($this->mockPluginLoader($this->never()), ['foo' => 'bar']);

        $this->assertSame(['foo' => 'bar'], $container->getManagerConfig());
    }

    public function testGetExtensionConfig()
    {
        $plugin = $this->getMock(ExtensionPluginInterface::class);

        $pluginLoader = $this->mockPluginLoader(
            $this->once(),
            [$plugin]
        );

        $container = new ContainerBuilder($pluginLoader, []);

        $extension = $this->getMock(ExtensionInterface::class);
        $extension
            ->expects($this->any())
            ->method('getAlias')
            ->willReturn('foobar')
        ;

        $plugin
            ->expects($this->once())
            ->method('getExtensionConfig')
            ->with('foobar', [['foo' => 'bar']])
            ->willReturn([['bar' => 'foo']])
        ;

        $container->registerExtension($extension);
        $container->loadFromExtension('foobar', ['foo' => 'bar']);

        $this->assertSame([['bar' => 'foo']], $container->getExtensionConfig('foobar'));
    }

    private function mockPluginLoader(
        \PHPUnit_Framework_MockObject_Matcher_InvokedRecorder $expects,
        array $plugins = []
    ) {
        $mock = $this->getMockBuilder(PluginLoader::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $mock
            ->expects($expects)
            ->method('getInstancesOf')
            ->with(PluginLoader::EXTENSION_PLUGINS)
            ->willReturn($plugins)
        ;

        return $mock;
    }
}
