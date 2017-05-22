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
    const FIXTURES_DIR = 'Fixtures/PluginLoader';

    public function testInstantiation()
    {
        $pluginLoader = new PluginLoader('foobar');

        $this->assertInstanceOf('Contao\ManagerPlugin\PluginLoader', $pluginLoader);
    }

    /**
     * @runInSeparateProcess
     */
    public function testLoadsPlugin()
    {
        include_once __DIR__.'/'.self::FIXTURES_DIR.'/FooBarPlugin.php';

        $pluginLoader = new PluginLoader(__DIR__.'/'.self::FIXTURES_DIR.'/installed.json');

        $plugins = $pluginLoader->getInstances();

        $this->assertCount(1, $plugins);
        $this->assertArrayHasKey('foo/bar-bundle', $plugins);
        $this->assertInstanceOf('Foo\Bar\FooBarPlugin', $plugins['foo/bar-bundle']);
    }

    public function testLoadFailsWhenPluginDoesNotExist()
    {
        $this->setExpectedException('RuntimeException', 'Bar\Foo\BarFooPlugin');

        $pluginLoader = new PluginLoader(__DIR__.'/'.self::FIXTURES_DIR.'/not-installed.json');

        $pluginLoader->getInstances();
    }

    /**
     * @runInSeparateProcess
     */
    public function testGetInstancesOfChecksInterface()
    {
        include_once __DIR__.'/'.self::FIXTURES_DIR.'/FooBarPlugin.php';
        include_once __DIR__.'/'.self::FIXTURES_DIR.'/FooConfigPlugin.php';

        $pluginLoader = new PluginLoader(__DIR__.'/'.self::FIXTURES_DIR.'/mixed.json');

        $plugins = $pluginLoader->getInstancesOf(PluginLoader::CONFIG_PLUGINS);

        $this->assertCount(1, $plugins);
        $this->assertArrayHasKey('foo/config-bundle', $plugins);
        $this->assertArrayNotHasKey('foo/bar-bundle', $plugins);
        $this->assertInstanceOf('Foo\Config\FooConfigPlugin', $plugins['foo/config-bundle']);
    }

    /**
     * @runInSeparateProcess
     */
    public function testLoadsGlobalManagerPlugin()
    {
        include_once __DIR__.'/'.self::FIXTURES_DIR.'/ContaoManagerPlugin.php';

        $pluginLoader = new PluginLoader(__DIR__.'/'.self::FIXTURES_DIR.'/empty.json');

        $plugins = $pluginLoader->getInstances();

        $this->assertCount(1, $plugins);
        $this->assertArrayHasKey('app', $plugins);
        $this->assertInstanceOf('ContaoManagerPlugin', $plugins['app']);
    }

    /**
     * @runInSeparateProcess
     */
    public function testLoadsManagerBundlePluginFirst()
    {
        include_once __DIR__.'/'.self::FIXTURES_DIR.'/FooBarPlugin.php';
        include_once __DIR__.'/'.self::FIXTURES_DIR.'/FooConfigPlugin.php';

        $pluginLoader = new PluginLoader(__DIR__.'/'.self::FIXTURES_DIR.'/manager-bundle.json');

        $plugins = $pluginLoader->getInstances();

        $this->assertCount(2, $plugins);
        $this->assertArrayHasKey('foo/bar-bundle', $plugins);
        $this->assertArrayHasKey('contao/manager-bundle', $plugins);
        $this->assertSame(['contao/manager-bundle', 'foo/bar-bundle'], array_keys($plugins));
    }

    /**
     * @runInSeparateProcess
     */
    public function testLoadOrdersPluginByDependencies()
    {
        include_once __DIR__.'/'.self::FIXTURES_DIR.'/FooBarPlugin.php';
        include_once __DIR__.'/'.self::FIXTURES_DIR.'/FooDependendPlugin.php';

        $pluginLoader = new PluginLoader(__DIR__.'/'.self::FIXTURES_DIR.'/dependencies.json');

        $plugins = $pluginLoader->getInstances();

        $this->assertCount(2, $plugins);
        $this->assertArrayHasKey('foo/bar-bundle', $plugins);
        $this->assertArrayHasKey('foo/dependend-bundle', $plugins);
        $this->assertSame(['foo/bar-bundle', 'foo/dependend-bundle'], array_keys($plugins));
    }

    /**
     * @runInSeparateProcess
     */
    public function testLoadIsOnlyRunOnce()
    {
        include_once __DIR__.'/'.self::FIXTURES_DIR.'/FooBarPlugin.php';

        /** @var PluginLoader|\PHPUnit_Framework_MockObject_MockObject $pluginLoader */
        $pluginLoader = $this->getMockBuilder(PluginLoader::class)
            ->setMethods(['orderPlugins'])
            ->setConstructorArgs([__DIR__.'/'.self::FIXTURES_DIR.'/installed.json'])
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

    public function testLoadMissingFile()
    {
        $this->setExpectedException('InvalidArgumentException', 'not found');

        $pluginLoader = new PluginLoader(__DIR__.'/'.self::FIXTURES_DIR.'/missing.json');

        $pluginLoader->getInstances();
    }

    public function testLoadInvalidJson()
    {
        $this->setExpectedException('RuntimeException');

        $pluginLoader = new PluginLoader(__DIR__.'/'.self::FIXTURES_DIR.'/invalid.json');

        $pluginLoader->getInstances();
    }
}
