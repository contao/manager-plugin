<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerPlugin\Tests\Composer;

use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;
use Composer\Repository\RepositoryInterface;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Composer\Installer;
use Foo\Bar\FooBarPlugin;
use Foo\Config\FooConfigPlugin;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

class InstallerTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $this->assertInstanceOf('Contao\ManagerPlugin\Composer\Installer', new Installer());
    }

    public function testDumpsPluginsFromRepository(): void
    {
        include_once __DIR__.'/../Fixtures/PluginLoader/FooBarPlugin.php';
        include_once __DIR__.'/../Fixtures/PluginLoader/FooConfigPlugin.php';

        $repository = $this->createMock(RepositoryInterface::class);
        $repository
            ->expects($this->once())
            ->method('getPackages')
            ->willReturn(
                [
                    $this->mockPackage('foo/bar-bundle', FooBarPlugin::class),
                    $this->mockPackage('foo/config-bundle', FooConfigPlugin::class),
                ]
            )
        ;

        $io = $this->createMock(IOInterface::class);

        $io
            ->expects($this->exactly(2))
            ->method('write')
            ->withConsecutive(
                [' - Added plugin for foo/bar-bundle', true, IOInterface::VERY_VERBOSE],
                [' - Added plugin for foo/config-bundle', true, IOInterface::VERY_VERBOSE]
            )
        ;

        $filesystem = $this->mockFilesystemAndCheckDump("
        \$this->plugins = \$plugins ?: [
            'foo/bar-bundle' => new \Foo\Bar\FooBarPlugin(),
            'foo/config-bundle' => new \Foo\Config\FooConfigPlugin()
        ];");

        $installer = new Installer($filesystem);
        $installer->dumpPlugins($repository, $io);
    }

    public function testAddsManagerPluginAtTop(): void
    {
        include_once __DIR__.'/../Fixtures/PluginLoader/FooBarPlugin.php';
        include_once __DIR__.'/../Fixtures/PluginLoader/FooConfigPlugin.php';

        $repository = $this->createMock(RepositoryInterface::class);
        $repository
            ->expects($this->once())
            ->method('getPackages')
            ->willReturn(
                [
                    $this->mockPackage('foo/config-bundle', FooConfigPlugin::class),
                    $this->mockPackage('contao/manager-bundle', FooBarPlugin::class),
                ]
            )
        ;

        $io = $this->createMock(IOInterface::class);

        $filesystem = $this->mockFilesystemAndCheckDump("
        \$this->plugins = \$plugins ?: [
            'contao/manager-bundle' => new \Foo\Bar\FooBarPlugin(),
            'foo/config-bundle' => new \Foo\Config\FooConfigPlugin()
        ];");

        $installer = new Installer($filesystem);
        $installer->dumpPlugins($repository, $io);
    }

    /**
     * @runInSeparateProcess
     */
    public function testAddsGlobalPluginIfClassExists(): void
    {
        include_once __DIR__.'/../Fixtures/PluginLoader/FooBarPlugin.php';

        $this->getMockBuilder(BundlePluginInterface::class)
            ->setMockClassName('ContaoManagerPlugin')
            ->getMock()
        ;

        $repository = $this->createMock(RepositoryInterface::class);
        $repository
            ->expects($this->once())
            ->method('getPackages')
            ->willReturn(
                [
                    $this->mockPackage('foo/bar-bundle', FooBarPlugin::class),
                ]
            )
        ;

        $io = $this->createMock(IOInterface::class);

        $filesystem = $this->mockFilesystemAndCheckDump("
        \$this->plugins = \$plugins ?: [
            'foo/bar-bundle' => new \Foo\Bar\FooBarPlugin(),
            'app' => new \ContaoManagerPlugin()
        ];");

        $installer = new Installer($filesystem);
        $installer->dumpPlugins($repository, $io);
    }

    public function testThrowsExceptionIfPluginClassDoesNotExist(): void
    {
        $repository = $this->createMock(RepositoryInterface::class);
        $repository
            ->expects($this->once())
            ->method('getPackages')
            ->willReturn(
                [
                    $this->mockPackage('foo/bar', '\Non\Existing\Plugin'),
                ]
            )
        ;

        $io = $this->createMock(IOInterface::class);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Plugin class "\Non\Existing\Plugin" not found');

        $installer = new Installer();
        $installer->dumpPlugins($repository, $io);
    }

    /**
     * @param string $name
     * @param string $plugin
     *
     * @return PackageInterface
     */
    private function mockPackage(string $name, string $plugin): PackageInterface
    {
        $package = $this->createMock(PackageInterface::class);

        $package
            ->expects($this->any())
            ->method('getName')
            ->willReturn($name)
        ;

        $package
            ->expects($this->once())
            ->method('getExtra')
            ->willReturn(['contao-manager-plugin' => $plugin])
        ;

        return $package;
    }

    /**
     * @param string $match
     *
     * @return Filesystem
     */
    private function mockFilesystemAndCheckDump(string $match): Filesystem
    {
        $filesystem = $this->createMock(Filesystem::class);

        $filesystem
            ->expects($this->once())
            ->method('dumpFile')
            ->with(
                \dirname(__DIR__, 2).'/src/Composer/../PluginLoader.php',
                $this->callback(
                    function ($content) use ($match) {
                        return false !== strpos($content, $match);
                    }
                )
            )
        ;

        return $filesystem;
    }
}
