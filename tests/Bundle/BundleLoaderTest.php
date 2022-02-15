<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerPlugin\Tests\Bundle;

use Contao\ManagerPlugin\Bundle\BundleLoader;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Config\ConfigResolverFactory;
use Contao\ManagerPlugin\Bundle\Config\ConfigResolverInterface;
use Contao\ManagerPlugin\Bundle\Config\ModuleConfig;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use Contao\ManagerPlugin\PluginLoader;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

class BundleLoaderTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $bundleLoader = new BundleLoader(
            $this->mockPluginLoader($this->never()),
            $this->mockConfigResolverFactory(0, false),
            $this->createMock(ParserInterface::class)
        );

        $this->assertInstanceOf('Contao\ManagerPlugin\Bundle\BundleLoader', $bundleLoader);
    }

    /**
     * @dataProvider getBundleConfigs
     */
    public function testReturnsTheBundleConfigs(array $plugins, int $configCount, bool $development): void
    {
        $bundleLoader = new BundleLoader(
            $this->mockPluginLoader($this->atLeastOnce(), $plugins),
            $this->mockConfigResolverFactory($configCount, $development),
            $this->createMock(ParserInterface::class)
        );

        $bundleLoader->getBundleConfigs($development);
    }

    /**
     * @return array<string, (array<BundlePluginInterface>|int|bool)>
     */
    public function getBundleConfigs(): array
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

    public function testReadsTheCacheFile(): void
    {
        $cacheFile = tempnam(sys_get_temp_dir(), 'BundleLoader_');

        file_put_contents(
            $cacheFile,
            sprintf(
                '<?php return %s;',
                var_export([new BundleConfig('foobar'), new ModuleConfig('legacy')], true)
            )
        );

        $bundleLoader = new BundleLoader(
            $this->mockPluginLoader($this->never()),
            $this->mockConfigResolverFactory(0, false),
            $this->createMock(ParserInterface::class)
        );

        $configs = $bundleLoader->getBundleConfigs(false, $cacheFile);

        $this->assertCount(2, $configs);
        $this->assertInstanceOf(BundleConfig::class, $configs[0]);
        $this->assertInstanceOf(ModuleConfig::class, $configs[1]);
        $this->assertSame('foobar', $configs[0]->getName());
        $this->assertSame('legacy', $configs[1]->getName());
    }

    public function testIgnoresTheCacheFileIfItIsEmpty(): void
    {
        $cacheFile = tempnam(sys_get_temp_dir(), 'BundleLoader_');

        $bundleLoader = new BundleLoader(
            $this->mockPluginLoader(
                $this->atLeastOnce(),
                [$this->mockBundlePlugin([new BundleConfig('foobar')])]
            ),
            $this->mockConfigResolverFactory(1, false),
            $this->createMock(ParserInterface::class),
            $this->createMock(Filesystem::class)
        );

        $bundleLoader->getBundleConfigs(false, $cacheFile);
    }

    public function testWritesTheCacheFile(): void
    {
        $cacheFile = sys_get_temp_dir().'/'.uniqid('BundleLoader_', true);

        $filesystem = $this->createMock(Filesystem::class);
        $filesystem
            ->expects($this->once())
            ->method('dumpFile')
            ->with($cacheFile, "<?php return array (\n);")
        ;

        $bundleLoader = new BundleLoader(
            $this->mockPluginLoader(
                $this->atLeastOnce(),
                [$this->mockBundlePlugin([new BundleConfig('foobar')])]
            ),
            $this->mockConfigResolverFactory(1, false),
            $this->createMock(ParserInterface::class),
            $filesystem
        );

        $bundleLoader->getBundleConfigs(false, $cacheFile);
    }

    /**
     * @return BundlePluginInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private function mockBundlePlugin(array $configs = []): BundlePluginInterface
    {
        $mock = $this->createMock(BundlePluginInterface::class);
        $mock
            ->method('getBundles')
            ->willReturn($configs)
        ;

        return $mock;
    }

    /**
     * @return PluginLoader|\PHPUnit_Framework_MockObject_MockObject
     */
    private function mockPluginLoader(\PHPUnit\Framework\MockObject\Rule\InvocationOrder $expects, array $plugins = []): PluginLoader
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
     * @return ConfigResolverFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private function mockConfigResolverFactory(int $addCount, bool $development): ConfigResolverFactory
    {
        $resolver = $this->createMock(ConfigResolverInterface::class);
        $resolver
            ->expects($this->exactly($addCount))
            ->method('add')
        ;

        $resolver
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
