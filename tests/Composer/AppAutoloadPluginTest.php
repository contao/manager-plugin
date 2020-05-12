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
use Composer\IO\IOInterface;
use Composer\Package\RootPackageInterface;
use Contao\ManagerPlugin\Composer\AppAutoloadPlugin;
use PHPUnit\Framework\TestCase;

class AppAutoloadPluginTest extends TestCase
{
    public function testAddsTheAppNamespaceIfNoneIsDefined(): void
    {
        $package = $this->createMock(RootPackageInterface::class);
        $package
            ->expects($this->once())
            ->method('getAutoload')
            ->willReturn([])
        ;

        $package
            ->expects($this->once())
            ->method('setAutoload')
            ->with(['psr-4' => ['App\\' => 'src/']])
        ;

        $composer = $this->createMock(Composer::class);
        $composer
            ->expects($this->once())
            ->method('getPackage')
            ->willReturn($package)
        ;

        $io = $this->createMock(IOInterface::class);
        $io
            ->expects($this->never())
            ->method($this->anything())
        ;

        (new AppAutoloadPlugin())->activate($composer, $io);
    }

    public function testDoesNotAddTheAppNamespaceIfOneIsDefined(): void
    {
        $package = $this->createMock(RootPackageInterface::class);
        $package
            ->expects($this->once())
            ->method('getAutoload')
            ->willReturn(['psr-4' => ['AppBundle\\' => 'src/']])
        ;

        $package
            ->expects($this->never())
            ->method('setAutoload')
        ;

        $composer = $this->createMock(Composer::class);
        $composer
            ->expects($this->once())
            ->method('getPackage')
            ->willReturn($package)
        ;

        $io = $this->createMock(IOInterface::class);
        $io
            ->expects($this->never())
            ->method($this->anything())
        ;

        (new AppAutoloadPlugin())->activate($composer, $io);
    }

    public function testImplementsTheAPI2Methods(): void
    {
        $plugin = new AppAutoloadPlugin();
        $composer = $this->createMock(Composer::class);
        $io = $this->createMock(IOInterface::class);

        $this->assertNull($plugin->deactivate($composer, $io));
        $this->assertNull($plugin->uninstall($composer, $io));
    }
}
