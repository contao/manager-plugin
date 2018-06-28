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

use Composer\Composer;
use Composer\Config;
use Composer\IO\IOInterface;
use Composer\Repository\RepositoryInterface;
use Composer\Repository\RepositoryManager;
use Composer\Script\Event;
use Composer\Script\ScriptEvents;
use Contao\ManagerPlugin\Composer\Installer;
use Contao\ManagerPlugin\Composer\Plugin;
use PHPUnit\Framework\TestCase;

class PluginTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $this->assertInstanceOf('Contao\ManagerPlugin\Composer\Plugin', new Plugin());
    }

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

        (new Plugin())->activate($composer, $io);
    }

    public function testDumpsPlugins(): void
    {
        $repository = $this->createMock(RepositoryInterface::class);
        $io = $this->createMock(IOInterface::class);
        $manager = $this->createMock(RepositoryManager::class);

        $manager
            ->method('getLocalRepository')
            ->willReturn($repository)
        ;

        $config = $this->createMock(Config::class);

        $config
            ->method('get')
            ->with('vendor-dir')
            ->willReturn(__DIR__.'/../Fixtures/Composer/null-vendor')
        ;

        $composer = $this->createMock(Composer::class);

        $composer
            ->method('getRepositoryManager')
            ->willReturn($manager)
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

        $installer = $this->createMock(Installer::class);

        $installer
            ->expects($this->once())
            ->method('dumpPlugins')
            ->with($repository, $io)
            ->willReturn(null)
        ;

        (new Plugin($installer))->dumpPlugins($event);
    }

    public function testLoadsAutoloadFileFromVendor()
    {
        $io = $this->createMock(IOInterface::class);
        $config = $this->createMock(Config::class);

        $config
            ->expects($this->once())
            ->method('get')
            ->with('vendor-dir')
            ->willReturn(__DIR__.'/../Fixtures/Composer/test-vendor')
        ;

        $composer = $this->createMock(Composer::class);

        $composer
            ->expects($this->once())
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

        $installer = $this->createMock(Installer::class);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('autoload.php successfully loaded');

        (new Plugin($installer))->dumpPlugins($event);
    }

    public function testSubscribesToInstallAndUpdateEvent(): void
    {
        $events = Plugin::getSubscribedEvents();

        $this->assertArrayHasKey(ScriptEvents::POST_INSTALL_CMD, $events);
        $this->assertArrayHasKey(ScriptEvents::POST_UPDATE_CMD, $events);
        $this->assertTrue(method_exists(Plugin::class, $events[ScriptEvents::POST_INSTALL_CMD]));
        $this->assertTrue(method_exists(Plugin::class, $events[ScriptEvents::POST_UPDATE_CMD]));
    }
}
