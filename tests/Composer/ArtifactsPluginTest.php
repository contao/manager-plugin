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
use Contao\ManagerPlugin\Composer\ArtifactsPlugin;
use PHPUnit\Framework\TestCase;

class ArtifactsPluginTest extends TestCase
{
    public function testAddsArtifactsRepository()
    {
        $io = $this->createMock(IOInterface::class);
        $repository = $this->createMock(RepositoryInterface::class);
        $repositoryManager = $this->createMock(RepositoryManager::class);

        $repositoryManager
            ->expects($this->once())
            ->method('createRepository')
            ->with('artifact', ['url' => __DIR__.'/../Fixtures/Composer/test-data/packages'])
            ->willReturn($repository)
        ;

        $repositoryManager
            ->expects($this->once())
            ->method('prependRepository')
            ->with($repository)
        ;

        $composer = $this->mockComposerWithDataDir(__DIR__.'/../Fixtures/Composer/test-data');

        $composer
            ->expects($this->atLeastOnce())
            ->method('getRepositoryManager')
            ->willReturn($repositoryManager)
        ;

        $plugin = new ArtifactsPlugin();
        $plugin->activate($composer, $io);
    }

    public function testDoesNothingWithoutPackagesDir()
    {
        $io = $this->createMock(IOInterface::class);
        $composer = $this->mockComposerWithDataDir(__DIR__.'/../Fixtures/Composer/null-data');

        $composer
            ->expects($this->never())
            ->method('getRepositoryManager')
        ;

        $plugin = new ArtifactsPlugin();
        $plugin->activate($composer, $io);
    }

    /**
     * @return Composer|\PHPUnit_Framework_MockObject_MockObject
     */
    private function mockComposerWithDataDir(string $dataDir): Composer
    {
        $config = $this->createMock(Config::class);
        $config
            ->expects($this->once())
            ->method('get')
            ->with('data-dir')
            ->willReturn($dataDir)
        ;

        $composer = $this->createMock(Composer::class);
        $composer
            ->expects($this->once())
            ->method('getConfig')
            ->willReturn($config)
        ;

        return $composer;
    }
}
