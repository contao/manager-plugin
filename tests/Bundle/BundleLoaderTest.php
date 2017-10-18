<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\ManagerPlugin\Tests\Bundle;

use Contao\ManagerPlugin\Bundle\BundleLoader;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Config\ConfigResolverFactory;
use Contao\ManagerPlugin\Bundle\Config\ConfigResolverInterface;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use Contao\ManagerPlugin\PluginLoader;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

class BundleLoaderTest extends TestCase
{
    public function testCanBeInstantiated()
    {
        $bundleLoader = new BundleLoader(
            $this->mockPluginLoader($this->never()),
            $this->mockConfigResolverFactory(0, false),
            $this->createMock(ParserInterface::class)
        );

        $this->assertInstanceOf('Contao\ManagerPlugin\Bundle\BundleLoader', $bundleLoader);
    }

    /**
     * @param array $plugins
     * @param int   $configCount
     * @param bool  $development
     *
     * @dataProvider getBundleConfigs
     */
    public function testReturnsTheBundleConfigs(array $plugins, $configCount, $development)
    {
        $bundleLoader = new BundleLoader(
            $this->mockPluginLoader($this->atLeastOnce(), $plugins),
            $this->mockConfigResolverFactory($configCount, $development),
            $this->createMock(ParserInterface::class)
        );

        $bundleLoader->getBundleConfigs($development);
    }

    /**
     * @return array
     */
    public function getBundleConfigs()
    {
        return [
            'Test correctly calls development mode' => [
                [
                    $this->mockBundlePlugin([new BundleConfig('foobar')]),
                ],
                1,
                true,
            ],
            'Test correctly calls production mode' => [
                [
                    $this->mockBundlePlugin([new BundleConfig('foobar')]),
                ],
                1,
                false,
            ],
            'Test correctly adds multiple configs from a plugin' => [
                [
                    $this->mockBundlePlugin([new BundleConfig('foo'), new BundleConfig('bar')]),
                ],
                2,
                true,
            ],
            'Test correctly adds config from multiple plugins' => [
                [
                    $this->mockBundlePlugin([new BundleConfig('foo')]),
                    $this->mockBundlePlugin([new BundleConfig('bar')]),
                ],
                2,
                false,
            ],
            'Test ignores plugin without bundles' => [
                [
                    $this->mockBundlePlugin([new BundleConfig('foo')]),
                    $this->mockBundlePlugin([new BundleConfig('bar')]),
                    $this->mockBundlePlugin(),
                ],
                2,
                false,
            ],
        ];
    }

    public function testReadsTheCacheFile()
    {
        $cacheFile = tempnam(sys_get_temp_dir(), 'BundleLoader_');

        file_put_contents($cacheFile, serialize([new BundleConfig('foobar')]));

        $bundleLoader = new BundleLoader(
            $this->mockPluginLoader($this->never()),
            $this->mockConfigResolverFactory(0, false),
            $this->createMock(ParserInterface::class)
        );

        $configs = $bundleLoader->getBundleConfigs(false, $cacheFile);

        $this->assertCount(1, $configs);
        $this->assertInstanceOf(BundleConfig::class, $configs[0]);
    }

    public function testIgnoresTheCacheFileIfItIsEmpty()
    {
        $cacheFile = tempnam(sys_get_temp_dir(), 'BundleLoader_');

        $bundleLoader = new BundleLoader(
            $this->mockPluginLoader($this->atLeastOnce(), [$this->mockBundlePlugin([new BundleConfig('foobar')])]),
            $this->mockConfigResolverFactory(1, false),
            $this->createMock(ParserInterface::class),
            $this->createMock(Filesystem::class)
        );

        $bundleLoader->getBundleConfigs(false, $cacheFile);
    }

    public function testWritesTheCacheFile()
    {
        $cacheFile = sys_get_temp_dir().'/'.uniqid('BundleLoader_', false);

        $this->assertFileNotExists($cacheFile);

        $filesystem = $this->createMock(Filesystem::class);

        $filesystem
            ->expects($this->once())
            ->method('dumpFile')
            ->with($cacheFile)
        ;

        $bundleLoader = new BundleLoader(
            $this->mockPluginLoader($this->atLeastOnce(), [$this->mockBundlePlugin([new BundleConfig('foobar')])]),
            $this->mockConfigResolverFactory(1, false),
            $this->createMock(ParserInterface::class),
            $filesystem
        );

        $bundleLoader->getBundleConfigs(false, $cacheFile);

        $this->assertFileNotExists($cacheFile);
    }

    /**
     * Mocks the bundle plugin.
     *
     * @param array $configs
     *
     * @return BundlePluginInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private function mockBundlePlugin(array $configs = [])
    {
        $mock = $this->createMock(BundlePluginInterface::class);

        $mock
            ->method('getBundles')
            ->willReturn($configs)
        ;

        return $mock;
    }

    /**
     * Mocks the plugin loader.
     *
     * @param \PHPUnit_Framework_MockObject_Matcher_InvokedRecorder $expects
     * @param array                                                 $plugins
     *
     * @return PluginLoader|\PHPUnit_Framework_MockObject_MockObject
     */
    private function mockPluginLoader(\PHPUnit_Framework_MockObject_Matcher_InvokedRecorder $expects, array $plugins = [])
    {
        $pluginLoader = $this->createMock(PluginLoader::class);

        $pluginLoader
            ->expects($expects)
            ->method('getInstancesOf')
            ->with(PluginLoader::BUNDLE_PLUGINS)
            ->willReturn($plugins)
        ;

        return $pluginLoader;
    }

    /**
     * Mocks the config resolver factory.
     *
     * @param int  $addCount
     * @param bool $development
     *
     * @return ConfigResolverFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private function mockConfigResolverFactory($addCount, $development)
    {
        $resolver = $this->createMock(ConfigResolverInterface::class);

        $resolver
            ->expects($this->exactly($addCount))
            ->method('add')
        ;

        $resolver
            ->expects($this->any())
            ->method('getBundleConfigs')
            ->with($development)
            ->willReturn([])
        ;

        $factory = $this->createMock(ConfigResolverFactory::class);

        $factory
            ->method('create')
            ->willReturn($resolver)
        ;

        return $factory;
    }
}
