<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerPlugin\Tests;

use Contao\ManagerPlugin\Config\ConfigPluginInterface;
use Contao\ManagerPlugin\Dependency\DependentPluginInterface;
use Contao\ManagerPlugin\PluginLoader;
use Foo\Bar\FooBarPlugin;
use Foo\Config\FooConfigPlugin;
use Foo\Dependend\FooDependendPlugin;
use PHPUnit\Framework\TestCase;

class PluginLoaderTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $pluginLoader = new PluginLoader(null, []);

        $this->assertInstanceOf('Contao\ManagerPlugin\PluginLoader', $pluginLoader);
    }

    public function testReturnsPlugins(): void
    {
        include_once __DIR__.'/Fixtures/PluginLoader/FooBarPlugin.php';

        $pluginLoader = new PluginLoader(
            null,
            [
                'foo/bar-bundle' => new FooBarPlugin(),
            ]
        );

        $this->assertArrayHasKey('foo/bar-bundle', $pluginLoader->getInstances());
        $this->assertInstanceOf(FooBarPlugin::class, $pluginLoader->getInstances()['foo/bar-bundle']);
    }

    public function testReturnsPluginsByType(): void
    {
        include_once __DIR__.'/Fixtures/PluginLoader/FooBarPlugin.php';
        include_once __DIR__.'/Fixtures/PluginLoader/FooConfigPlugin.php';
        include_once __DIR__.'/Fixtures/PluginLoader/FooDependendPlugin.php';

        $pluginLoader = new PluginLoader(
            null,
            [
                'foo/bar-bundle' => new FooBarPlugin(),
                'foo/config-bundle' => new FooConfigPlugin(),
                'foo/dependent-bundle' => new FooDependendPlugin(),
            ]
        );

        $this->assertArrayHasKey('foo/config-bundle', $pluginLoader->getInstancesOf(ConfigPluginInterface::class));
        $this->assertInstanceOf(FooConfigPlugin::class, $pluginLoader->getInstances()['foo/config-bundle']);
        $this->assertArrayNotHasKey('foo/bar-bundle', $pluginLoader->getInstancesOf(ConfigPluginInterface::class));
        $this->assertArrayNotHasKey('foo/dependent-bundle', $pluginLoader->getInstancesOf(ConfigPluginInterface::class));

        $this->assertArrayHasKey('foo/dependent-bundle', $pluginLoader->getInstancesOf(DependentPluginInterface::class));
        $this->assertInstanceOf(FooDependendPlugin::class, $pluginLoader->getInstances()['foo/dependent-bundle']);
        $this->assertArrayNotHasKey('foo/bar-bundle', $pluginLoader->getInstancesOf(DependentPluginInterface::class));
        $this->assertArrayNotHasKey('foo/config-bundle', $pluginLoader->getInstancesOf(DependentPluginInterface::class));
    }

    public function testReturnsReversedPluginOrder(): void
    {
        $pluginLoader = new PluginLoader(
            null,
            [
                'foo/config1-bundle' => new FooConfigPlugin(),
                'foo/config2-bundle' => new FooConfigPlugin(),
                'foo/config3-bundle' => new FooConfigPlugin(),
            ]
        );

        $keys = array_keys($pluginLoader->getInstancesOf(ConfigPluginInterface::class, true));

        $this->assertCount(3, $keys);
        $this->assertSame('foo/config3-bundle', $keys[0]);
        $this->assertSame('foo/config2-bundle', $keys[1]);
        $this->assertSame('foo/config1-bundle', $keys[2]);
    }

    public function testLegacyUpdateFilesAreIdentical(): void
    {
        $this->assertFileEquals(__DIR__.'/../src/PluginLoader.php', __DIR__.'/../src/Resources/PluginLoader.php');
    }
}
