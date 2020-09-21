<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerPlugin\Tests\Config;

use Contao\ManagerPlugin\Config\ContainerBuilder;
use Contao\ManagerPlugin\Config\ExtensionPluginInterface;
use Contao\ManagerPlugin\PluginLoader;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;

class ContainerBuilderTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $container = new ContainerBuilder($this->mockPluginLoader($this->never()), []);

        $this->assertInstanceOf('Contao\ManagerPlugin\Config\ContainerBuilder', $container);
    }

    public function testReturnsTheManagerConfig(): void
    {
        $container = new ContainerBuilder($this->mockPluginLoader($this->never()), ['foo' => 'bar']);

        $this->assertSame(['foo' => 'bar'], $container->getManagerConfig());
    }

    public function testReturnsTheExtensionConfig(): void
    {
        $plugin = $this->createMock(ExtensionPluginInterface::class);
        $plugin
            ->expects($this->once())
            ->method('getExtensionConfig')
            ->with('foobar', [['foo' => 'bar']])
            ->willReturn([['bar' => 'foo']])
        ;

        $extension = $this->createMock(ExtensionInterface::class);
        $extension
            ->method('getAlias')
            ->willReturn('foobar')
        ;

        $container = new ContainerBuilder($this->mockPluginLoader($this->once(), [$plugin]), []);
        $container->registerExtension($extension);
        $container->loadFromExtension('foobar', ['foo' => 'bar']);

        $this->assertSame([['bar' => 'foo']], $container->getExtensionConfig('foobar'));
    }

    /**
     * @return PluginLoader|\PHPUnit_Framework_MockObject_MockObject
     */
    private function mockPluginLoader(\PHPUnit\Framework\MockObject\Rule\InvokedCount $expects, array $plugins = []): PluginLoader
    {
        $pluginLoader = $this->createMock(PluginLoader::class);
        $pluginLoader
            ->expects($expects)
            ->method('getInstancesOf')
            ->with(PluginLoader::EXTENSION_PLUGINS)
            ->willReturn($plugins)
        ;

        return $pluginLoader;
    }
}
