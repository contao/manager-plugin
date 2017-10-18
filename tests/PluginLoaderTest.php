<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\ManagerPlugin\Test;

use Contao\ManagerPlugin\PluginLoader;

class PluginLoaderTest extends \PHPUnit_Framework_TestCase
{
    public function testCanBeInstantiated()
    {
        $pluginLoader = new PluginLoader('foobar');

        $this->assertInstanceOf('Contao\ManagerPlugin\PluginLoader', $pluginLoader);
    }

    /**
     * @runInSeparateProcess
     */
    public function testReturnsTheActivePlugins()
    {
        include_once __DIR__.'/Fixtures/PluginLoader/FooBarPlugin.php';

        $pluginLoader = new PluginLoader(__DIR__.'/Fixtures/PluginLoader/installed.json');
        $plugins = $pluginLoader->getInstances();

        $this->assertCount(1, $plugins);
        $this->assertArrayHasKey('foo/bar-bundle', $plugins);
        $this->assertInstanceOf('Foo\Bar\FooBarPlugin', $plugins['foo/bar-bundle']);
    }

    public function testFailsIfAPluginDoesNotExist()
    {
        $pluginLoader = new PluginLoader(__DIR__.'/Fixtures/PluginLoader/not-installed.json');

        $this->expectException('RuntimeException');

        $pluginLoader->getInstances();
    }

    /**
     * @runInSeparateProcess
     */
    public function testReturnsTheActiveConfigPlugins()
    {
        include_once __DIR__.'/Fixtures/PluginLoader/FooBarPlugin.php';
        include_once __DIR__.'/Fixtures/PluginLoader/FooConfigPlugin.php';

        $pluginLoader = new PluginLoader(__DIR__.'/Fixtures/PluginLoader/mixed.json');
        $plugins = $pluginLoader->getInstancesOf(PluginLoader::CONFIG_PLUGINS);

        $this->assertCount(1, $plugins);
        $this->assertArrayHasKey('foo/config-bundle', $plugins);
        $this->assertArrayNotHasKey('foo/bar-bundle', $plugins);
        $this->assertInstanceOf('Foo\Config\FooConfigPlugin', $plugins['foo/config-bundle']);
    }

    /**
     * @runInSeparateProcess
     */
    public function testLoadsTheContaoManagerPlugin()
    {
        include_once __DIR__.'/Fixtures/PluginLoader/ContaoManagerPlugin.php';

        $pluginLoader = new PluginLoader(__DIR__.'/Fixtures/PluginLoader/empty.json');
        $plugins = $pluginLoader->getInstances();

        $this->assertCount(1, $plugins);
        $this->assertArrayHasKey('app', $plugins);
        $this->assertInstanceOf('ContaoManagerPlugin', $plugins['app']);
    }

    /**
     * @runInSeparateProcess
     */
    public function testLoadsTheManagerBundlePluginFirst()
    {
        include_once __DIR__.'/Fixtures/PluginLoader/FooBarPlugin.php';
        include_once __DIR__.'/Fixtures/PluginLoader/FooConfigPlugin.php';

        $pluginLoader = new PluginLoader(__DIR__.'/Fixtures/PluginLoader/manager-bundle.json');
        $plugins = $pluginLoader->getInstances();

        $this->assertCount(2, $plugins);
        $this->assertArrayHasKey('foo/bar-bundle', $plugins);
        $this->assertArrayHasKey('contao/manager-bundle', $plugins);
        $this->assertSame(['contao/manager-bundle', 'foo/bar-bundle'], array_keys($plugins));
    }

    /**
     * @runInSeparateProcess
     */
    public function testOrdersThePluginsByTheirDependencies()
    {
        include_once __DIR__.'/Fixtures/PluginLoader/FooBarPlugin.php';
        include_once __DIR__.'/Fixtures/PluginLoader/FooDependendPlugin.php';

        $pluginLoader = new PluginLoader(__DIR__.'/Fixtures/PluginLoader/dependencies.json');
        $plugins = $pluginLoader->getInstances();

        $this->assertCount(2, $plugins);
        $this->assertArrayHasKey('foo/bar-bundle', $plugins);
        $this->assertArrayHasKey('foo/dependend-bundle', $plugins);
        $this->assertSame(['foo/bar-bundle', 'foo/dependend-bundle'], array_keys($plugins));
    }

    public function testOrdersThePluginsOnlyOnce()
    {
        include_once __DIR__.'/Fixtures/PluginLoader/FooBarPlugin.php';

        /** @var PluginLoader|\PHPUnit_Framework_MockObject_MockObject $pluginLoader */
        $pluginLoader = $this
            ->getMockBuilder(PluginLoader::class)
            ->setMethods(['orderPlugins'])
            ->setConstructorArgs([__DIR__.'/Fixtures/PluginLoader/installed.json'])
            ->getMock()
        ;

        $pluginLoader
            ->expects($this->once())
            ->method('orderPlugins')
            ->willReturnArgument(0)
        ;

        $pluginLoader->getInstances();
        $pluginLoader->getInstances();
    }

    public function testFailsIfTheJsonFileDoesNotExist()
    {
        $pluginLoader = new PluginLoader(__DIR__.'/Fixtures/PluginLoader/missing.json');

        $this->expectException('InvalidArgumentException');

        $pluginLoader->getInstances();
    }

    public function testFailsIfTheJsonDataIsInvalid()
    {
        $pluginLoader = new PluginLoader(__DIR__.'/Fixtures/PluginLoader/invalid.json');

        $this->expectException('RuntimeException');

        $pluginLoader->getInstances();
    }

    public function testReturnsDisabledPackages()
    {
        $pluginLoader = new PluginLoader(__DIR__.'/Fixtures/PluginLoader/empty.json');

        $pluginLoader->setDisabledPackages(['foo', 'bar']);
        $this->assertSame(['foo', 'bar'], $pluginLoader->getDisabledPackages());
    }

    public function testDoesNotLoadDisabledPackages()
    {
        include_once __DIR__.'/Fixtures/PluginLoader/FooBarPlugin.php';
        include_once __DIR__.'/Fixtures/PluginLoader/FooConfigPlugin.php';

        $pluginLoader = new PluginLoader(__DIR__.'/Fixtures/PluginLoader/manager-bundle.json');

        $pluginLoader->setDisabledPackages(['foo/bar-bundle']);

        $plugins = $pluginLoader->getInstances();

        $this->assertArrayHasKey('contao/manager-bundle', $plugins);
        $this->assertArrayNotHasKey('foo/bar-bundle', $plugins);
    }
}
