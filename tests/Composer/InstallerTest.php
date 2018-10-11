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
use Composer\Package\AliasPackage;
use Composer\Package\CompletePackage;
use Composer\Package\PackageInterface;
use Composer\Repository\RepositoryInterface;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Composer\Installer;
use Foo\Bar\FooBarPlugin;
use Foo\Config\FooConfigPlugin;
use Foo\Console\FooConsolePlugin;
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
        include_once __DIR__.'/../Fixtures/PluginLoader/FooConsolePlugin.php';

        $bundles = [
            'foo/config-bundle' => FooConfigPlugin::class,
            'foo/console-bundle' => FooConsolePlugin::class,
        ];

        $repository = $this->createMock(RepositoryInterface::class);
        $repository
            ->expects($this->once())
            ->method('getPackages')
            ->willReturn([
                $this->mockPackage('foo/bar-bundle', FooBarPlugin::class),
                $this->mockMultiPackage('foo/config-bundle', $bundles, ['foo/console-bundle' => 'self.version']),
            ])
        ;

        $io = $this->createMock(IOInterface::class);
        $io
            ->expects($this->exactly(3))
            ->method('write')
            ->withConsecutive(
                [' - Added plugin for foo/bar-bundle', true, IOInterface::VERY_VERBOSE],
                [' - Added plugin for foo/config-bundle', true, IOInterface::VERY_VERBOSE],
                [' - Added plugin for foo/console-bundle', true, IOInterface::VERY_VERBOSE]
            )
        ;

        $filesystem = $this->mockFilesystemAndCheckDump("
        \$this->plugins = \$plugins ?: [
            'foo/bar-bundle' => new \Foo\Bar\FooBarPlugin(),
            'foo/config-bundle' => new \Foo\Config\FooConfigPlugin(),
            'foo/console-bundle' => new \Foo\Console\FooConsolePlugin(),
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
            ->willReturn([
                $this->mockPackage('foo/config-bundle', FooConfigPlugin::class),
                $this->mockPackage('contao/manager-bundle', FooBarPlugin::class),
            ])
        ;

        $io = $this->createMock(IOInterface::class);

        $filesystem = $this->mockFilesystemAndCheckDump("
        \$this->plugins = \$plugins ?: [
            'contao/manager-bundle' => new \Foo\Bar\FooBarPlugin(),
            'foo/config-bundle' => new \Foo\Config\FooConfigPlugin(),
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

        $this
            ->getMockBuilder(BundlePluginInterface::class)
            ->setMockClassName('ContaoManagerPlugin')
            ->getMock()
        ;

        $repository = $this->createMock(RepositoryInterface::class);
        $repository
            ->expects($this->once())
            ->method('getPackages')
            ->willReturn([
                $this->mockPackage('foo/bar-bundle', FooBarPlugin::class),
            ])
        ;

        $io = $this->createMock(IOInterface::class);

        $filesystem = $this->mockFilesystemAndCheckDump("
        \$this->plugins = \$plugins ?: [
            'foo/bar-bundle' => new \Foo\Bar\FooBarPlugin(),
            'app' => new \ContaoManagerPlugin(),
        ];");

        $installer = new Installer($filesystem);
        $installer->dumpPlugins($repository, $io);
    }

    /**
     * @runInSeparateProcess
     */
    public function testAddsAppPluginIfClassExists(): void
    {
        include_once __DIR__.'/../Fixtures/PluginLoader/FooBarPlugin.php';
        include_once __DIR__.'/../Fixtures/PluginLoader/Plugin.php';

        $this
            ->getMockBuilder('App\ContaoManager\Plugin')
            ->getMock()
        ;

        $repository = $this->createMock(RepositoryInterface::class);
        $repository
            ->expects($this->once())
            ->method('getPackages')
            ->willReturn([
                $this->mockPackage('foo/bar-bundle', FooBarPlugin::class),
            ])
        ;

        $io = $this->createMock(IOInterface::class);

        $filesystem = $this->mockFilesystemAndCheckDump("
        \$this->plugins = \$plugins ?: [
            'foo/bar-bundle' => new \Foo\Bar\FooBarPlugin(),
            'app' => new \App\ContaoManager\Plugin(),
        ];");

        $installer = new Installer($filesystem);
        $installer->dumpPlugins($repository, $io);
    }

    public function testIgnoresAliasPackages(): void
    {
        include_once __DIR__.'/../Fixtures/PluginLoader/FooBarPlugin.php';
        include_once __DIR__.'/../Fixtures/PluginLoader/FooConfigPlugin.php';

        $repository = $this->createMock(RepositoryInterface::class);
        $repository
            ->expects($this->once())
            ->method('getPackages')
            ->willReturn([
                $this->mockPackage('foo/bar-bundle', FooBarPlugin::class, true),
                $this->mockPackage('foo/config-bundle', FooConfigPlugin::class),
            ])
        ;

        $io = $this->createMock(IOInterface::class);
        $io
            ->expects($this->once())
            ->method('write')
            ->withConsecutive(
                [' - Added plugin for foo/config-bundle', true, IOInterface::VERY_VERBOSE]
            )
        ;

        $filesystem = $this->mockFilesystemAndCheckDump("
        \$this->plugins = \$plugins ?: [
            'foo/config-bundle' => new \Foo\Config\FooConfigPlugin(),
        ];");

        $installer = new Installer($filesystem);
        $installer->dumpPlugins($repository, $io);
    }

    public function testFailsIfThePluginClassDoesNotExist(): void
    {
        $repository = $this->createMock(RepositoryInterface::class);
        $repository
            ->expects($this->once())
            ->method('getPackages')
            ->willReturn([
                $this->mockPackage('foo/bar', '\Non\Existing\Plugin'),
            ])
        ;

        $io = $this->createMock(IOInterface::class);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('The plugin class "\Non\Existing\Plugin" was not found');

        $installer = new Installer();
        $installer->dumpPlugins($repository, $io);
    }

    public function testFailsIfAnAdditionalPackageIsNotReplaced(): void
    {
        include_once __DIR__.'/../Fixtures/PluginLoader/FooBarPlugin.php';
        include_once __DIR__.'/../Fixtures/PluginLoader/FooConfigPlugin.php';
        include_once __DIR__.'/../Fixtures/PluginLoader/FooConsolePlugin.php';

        $bundles = [
            'foo/config-bundle' => FooConfigPlugin::class,
            'foo/console-bundle' => FooConsolePlugin::class,
        ];

        $repository = $this->createMock(RepositoryInterface::class);
        $repository
            ->expects($this->once())
            ->method('getPackages')
            ->willReturn([
                $this->mockPackage('foo/bar-bundle', FooBarPlugin::class),
                $this->mockMultiPackage('foo/config-bundle', $bundles, []),
            ])
        ;

        $io = $this->createMock(IOInterface::class);
        $io
            ->expects($this->once())
            ->method('write')
            ->withConsecutive(
                [' - Added plugin for foo/bar-bundle', true, IOInterface::VERY_VERBOSE]
            )
        ;

        $installer = new Installer();

        $this->expectException('RuntimeException');
        $this->expectExceptionMessage('The package "foo/console-bundle" is not replaced by "foo/config-bundle".');

        $installer->dumpPlugins($repository, $io);
    }

    public function testFailsIfAPackageIsRegisteredTwice(): void
    {
        include_once __DIR__.'/../Fixtures/PluginLoader/FooBarPlugin.php';
        include_once __DIR__.'/../Fixtures/PluginLoader/FooConfigPlugin.php';

        $repository = $this->createMock(RepositoryInterface::class);
        $repository
            ->expects($this->once())
            ->method('getPackages')
            ->willReturn([
                $this->mockPackage('foo/bar-bundle', FooBarPlugin::class),
                $this->mockPackage('foo/bar-bundle', FooBarPlugin::class),
            ])
        ;

        $io = $this->createMock(IOInterface::class);
        $io
            ->expects($this->once())
            ->method('write')
            ->withConsecutive(
                [' - Added plugin for foo/bar-bundle', true, IOInterface::VERY_VERBOSE]
            )
        ;

        $installer = new Installer();

        $this->expectException('RuntimeException');
        $this->expectExceptionMessage('The package "foo/bar-bundle" cannot be registered twice.');

        $installer->dumpPlugins($repository, $io);
    }

    private function mockPackage(string $name, string $plugin, bool $isAlias = false): PackageInterface
    {
        if ($isAlias) {
            $package = $this->createMock(AliasPackage::class);
        } else {
            $package = $this->createMock(CompletePackage::class);
        }

        $package
            ->method('getName')
            ->willReturn($name)
        ;

        $package
            ->method('getExtra')
            ->willReturn(['contao-manager-plugin' => $plugin])
        ;

        return $package;
    }

    private function mockMultiPackage(string $name, array $plugin, array $replaces): PackageInterface
    {
        $package = $this->createMock(CompletePackage::class);
        $package
            ->method('getName')
            ->willReturn($name)
        ;

        $package
            ->method('getExtra')
            ->willReturn(['contao-manager-plugin' => $plugin])
        ;

        $package
            ->method('getReplaces')
            ->willReturn($replaces)
        ;

        return $package;
    }

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
