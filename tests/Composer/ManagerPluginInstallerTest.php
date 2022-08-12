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

use App\ContaoManager\Plugin;
use Composer\Composer;
use Composer\Config;
use Composer\IO\IOInterface;
use Composer\Package\AliasPackage;
use Composer\Package\CompletePackage;
use Composer\Package\PackageInterface;
use Composer\Repository\InstalledRepositoryInterface;
use Composer\Repository\RepositoryManager;
use Composer\Script\Event;
use Composer\Script\ScriptEvents;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Composer\ManagerPluginInstaller;
use Foo\Bar\FooBarPlugin;
use Foo\Config\FooConfigPlugin;
use Foo\Console\FooConsolePlugin;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

class ManagerPluginInstallerTest extends TestCase
{
    public function testDoesNothingOnActivation(): void
    {
        $composer = $this->createMock(Composer::class);
        $composer
            ->expects($this->never())
            ->method($this->anything())
        ;

        $io = $this->createMock(IOInterface::class);
        $io
            ->expects($this->never())
            ->method($this->anything())
        ;

        (new ManagerPluginInstaller())->activate($composer, $io);
    }

    public function testSubscribesToInstallAndUpdateEvent(): void
    {
        $events = ManagerPluginInstaller::getSubscribedEvents();

        $this->assertArrayHasKey(ScriptEvents::POST_INSTALL_CMD, $events);
        $this->assertArrayHasKey(ScriptEvents::POST_UPDATE_CMD, $events);
        $this->assertTrue(method_exists(ManagerPluginInstaller::class, $events[ScriptEvents::POST_INSTALL_CMD]));
        $this->assertTrue(method_exists(ManagerPluginInstaller::class, $events[ScriptEvents::POST_UPDATE_CMD]));
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

        $repository = $this->createMock(InstalledRepositoryInterface::class);
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
            ->expects($this->exactly(5))
            ->method('write')
            ->withConsecutive(
                ['<info>contao/manager-plugin:</info> Dumping generated plugins file...'],
                [' - Added plugin for foo/bar-bundle', true, IOInterface::VERY_VERBOSE],
                [' - Added plugin for foo/config-bundle', true, IOInterface::VERY_VERBOSE],
                [' - Added plugin for foo/console-bundle', true, IOInterface::VERY_VERBOSE],
                ['<info>contao/manager-plugin:</info> ...done dumping generated plugins file']
            )
        ;

        $filesystem = $this->mockFilesystemAndCheckDump("<?php
return array (
  'foo/bar-bundle' => new \\Foo\\Bar\\FooBarPlugin(),
  'foo/config-bundle' => new \\Foo\\Config\\FooConfigPlugin(),
  'foo/console-bundle' => new \\Foo\\Console\\FooConsolePlugin(),
);");

        $installer = new ManagerPluginInstaller($filesystem);
        $installer->dumpPlugins($this->mockEventWithRepositoryAndIO($repository, $io));
    }

    public function testDumpsEmptyPluginsWithoutPackages(): void
    {
        $repository = $this->createMock(InstalledRepositoryInterface::class);
        $repository
            ->expects($this->once())
            ->method('getPackages')
            ->willReturn([])
        ;

        $io = $this->createMock(IOInterface::class);
        $io
            ->expects($this->exactly(2))
            ->method('write')
            ->withConsecutive(
                ['<info>contao/manager-plugin:</info> Dumping generated plugins file...'],
                ['<info>contao/manager-plugin:</info> ...done dumping generated plugins file']
            )
        ;

        $filesystem = $this->mockFilesystemAndCheckDump('<?php
return array (
);');

        $installer = new ManagerPluginInstaller($filesystem);
        $installer->dumpPlugins($this->mockEventWithRepositoryAndIO($repository, $io));
    }

    public function testAddsManagerPluginAtTop(): void
    {
        include_once __DIR__.'/../Fixtures/PluginLoader/FooBarPlugin.php';
        include_once __DIR__.'/../Fixtures/PluginLoader/FooConfigPlugin.php';

        $repository = $this->createMock(InstalledRepositoryInterface::class);
        $repository
            ->expects($this->once())
            ->method('getPackages')
            ->willReturn([
                $this->mockPackage('foo/config-bundle', FooConfigPlugin::class),
                $this->mockPackage('contao/manager-bundle', FooBarPlugin::class),
            ])
        ;

        $io = $this->createMock(IOInterface::class);

        $filesystem = $this->mockFilesystemAndCheckDump("<?php
return array (
  'contao/manager-bundle' => new \\Foo\\Bar\\FooBarPlugin(),
  'foo/config-bundle' => new \\Foo\\Config\\FooConfigPlugin(),
);");

        $installer = new ManagerPluginInstaller($filesystem);
        $installer->dumpPlugins($this->mockEventWithRepositoryAndIO($repository, $io));
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testAddsGlobalPluginIfClassExists(): void
    {
        include_once __DIR__.'/../Fixtures/PluginLoader/FooBarPlugin.php';

        $this
            ->getMockBuilder(BundlePluginInterface::class)
            ->setMockClassName('ContaoManagerPlugin')
            ->getMock()
        ;

        $repository = $this->createMock(InstalledRepositoryInterface::class);
        $repository
            ->expects($this->once())
            ->method('getPackages')
            ->willReturn([
                $this->mockPackage('foo/bar-bundle', FooBarPlugin::class),
            ])
        ;

        $io = $this->createMock(IOInterface::class);

        $filesystem = $this->mockFilesystemAndCheckDump("<?php
return array (
  'foo/bar-bundle' => new \\Foo\\Bar\\FooBarPlugin(),
  'app' => new \\ContaoManagerPlugin(),
);");

        $installer = new ManagerPluginInstaller($filesystem);
        $installer->dumpPlugins($this->mockEventWithRepositoryAndIO($repository, $io));
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testAddsAppPluginIfClassExists(): void
    {
        include_once __DIR__.'/../Fixtures/PluginLoader/FooBarPlugin.php';
        include_once __DIR__.'/../Fixtures/PluginLoader/Plugin.php';

        $this
            ->getMockBuilder(Plugin::class)
            ->getMock()
        ;

        $repository = $this->createMock(InstalledRepositoryInterface::class);
        $repository
            ->expects($this->once())
            ->method('getPackages')
            ->willReturn([
                $this->mockPackage('foo/bar-bundle', FooBarPlugin::class),
            ])
        ;

        $io = $this->createMock(IOInterface::class);

        $filesystem = $this->mockFilesystemAndCheckDump("<?php
return array (
  'foo/bar-bundle' => new \\Foo\\Bar\\FooBarPlugin(),
  'app' => new \\App\\ContaoManager\\Plugin(),
);");

        $installer = new ManagerPluginInstaller($filesystem);
        $installer->dumpPlugins($this->mockEventWithRepositoryAndIO($repository, $io));
    }

    public function testIgnoresAliasPackages(): void
    {
        include_once __DIR__.'/../Fixtures/PluginLoader/FooBarPlugin.php';
        include_once __DIR__.'/../Fixtures/PluginLoader/FooConfigPlugin.php';

        $repository = $this->createMock(InstalledRepositoryInterface::class);
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
            ->expects($this->exactly(3))
            ->method('write')
            ->withConsecutive(
                ['<info>contao/manager-plugin:</info> Dumping generated plugins file...'],
                [' - Added plugin for foo/config-bundle', true, IOInterface::VERY_VERBOSE],
                ['<info>contao/manager-plugin:</info> ...done dumping generated plugins file']
            )
        ;

        $filesystem = $this->mockFilesystemAndCheckDump("<?php
return array (
  'foo/config-bundle' => new \\Foo\\Config\\FooConfigPlugin(),
);");

        $installer = new ManagerPluginInstaller($filesystem);
        $installer->dumpPlugins($this->mockEventWithRepositoryAndIO($repository, $io));
    }

    public function testFailsIfThePluginClassDoesNotExist(): void
    {
        $repository = $this->createMock(InstalledRepositoryInterface::class);
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

        $installer = new ManagerPluginInstaller();
        $installer->dumpPlugins($this->mockEventWithRepositoryAndIO($repository, $io));
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

        $repository = $this->createMock(InstalledRepositoryInterface::class);
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
            ->expects($this->exactly(2))
            ->method('write')
            ->withConsecutive(
                ['<info>contao/manager-plugin:</info> Dumping generated plugins file...'],
                [' - Added plugin for foo/bar-bundle', true, IOInterface::VERY_VERBOSE]
            )
        ;

        $installer = new ManagerPluginInstaller();

        $this->expectException('RuntimeException');
        $this->expectExceptionMessage('The package "foo/console-bundle" is not replaced by "foo/config-bundle".');

        $installer->dumpPlugins($this->mockEventWithRepositoryAndIO($repository, $io));
    }

    public function testFailsIfAPackageIsRegisteredTwice(): void
    {
        include_once __DIR__.'/../Fixtures/PluginLoader/FooBarPlugin.php';
        include_once __DIR__.'/../Fixtures/PluginLoader/FooConfigPlugin.php';

        $repository = $this->createMock(InstalledRepositoryInterface::class);
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
            ->expects($this->exactly(2))
            ->method('write')
            ->withConsecutive(
                ['<info>contao/manager-plugin:</info> Dumping generated plugins file...'],
                [' - Added plugin for foo/bar-bundle', true, IOInterface::VERY_VERBOSE]
            )
        ;

        $installer = new ManagerPluginInstaller();

        $this->expectException('RuntimeException');
        $this->expectExceptionMessage('The package "foo/bar-bundle" cannot be registered twice.');

        $installer->dumpPlugins($this->mockEventWithRepositoryAndIO($repository, $io));
    }

    public function testImplementsTheAPI2Methods(): void
    {
        $plugin = new ManagerPluginInstaller();
        $composer = $this->createMock(Composer::class);
        $io = $this->createMock(IOInterface::class);

        $this->assertNull($plugin->deactivate($composer, $io));
        $this->assertNull($plugin->uninstall($composer, $io));
    }

    /**
     * @return PackageInterface&MockObject
     */
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

    /**
     * @return PackageInterface&MockObject
     */
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

    /**
     * @return Filesystem&MockObject
     */
    private function mockFilesystemAndCheckDump(string $match): Filesystem
    {
        $filesystem = $this->createMock(Filesystem::class);
        $filesystem
            ->expects($this->once())
            ->method('dumpFile')
            ->with(
                \dirname(__DIR__, 2).'/src/../.generated/plugins.php',
                $this->callback(
                    static function ($content) use ($match) {
                        return false !== strpos($content, $match);
                    }
                )
            )
        ;

        return $filesystem;
    }

    /**
     * @return Event&MockObject
     */
    private function mockEventWithRepositoryAndIO(InstalledRepositoryInterface $repository, IOInterface $io): Event
    {
        $config = $this->createMock(Config::class);
        $config
            ->method('get')
            ->with('vendor-dir')
            ->willReturn(__DIR__.'/../Fixtures/Composer/null-vendor')
        ;

        $repositoryManager = $this->createMock(RepositoryManager::class);
        $repositoryManager
            ->method('getLocalRepository')
            ->willReturn($repository)
        ;

        $composer = $this->createMock(Composer::class);
        $composer
            ->method('getRepositoryManager')
            ->willReturn($repositoryManager)
        ;

        $composer
            ->method('getConfig')
            ->willReturn($config)
        ;

        $event = $this->createMock(Event::class);
        $event
            ->method('getComposer')
            ->willReturn($composer)
        ;

        $event
            ->method('getIO')
            ->willReturn($io)
        ;

        return $event;
    }
}
