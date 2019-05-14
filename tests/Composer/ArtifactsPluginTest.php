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
use Composer\Package\PackageInterface;
use Composer\Package\RootPackageInterface;
use Composer\Repository\RepositoryInterface;
use Composer\Repository\RepositoryManager;
use Contao\ManagerPlugin\Composer\ArtifactsPlugin;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ArtifactsPluginTest extends TestCase
{
    public function testAddsArtifactsRepository(): void
    {
        $repository = $this->createMock(RepositoryInterface::class);
        $repository
            ->method('getPackages')
            ->willReturn([$this->createMock(PackageInterface::class)])
        ;

        $repositoryManager = $this->createMock(RepositoryManager::class);
        $repositoryManager
            ->expects($this->once())
            ->method('createRepository')
            ->with('artifact', ['url' => __DIR__.'/../Fixtures/Composer/artifact-data/packages'])
            ->willReturn($repository)
        ;

        $repositoryManager
            ->expects($this->once())
            ->method('addRepository')
            ->with($repository)
        ;

        $config = $this->mockConfigWithDataDir(__DIR__.'/../Fixtures/Composer/artifact-data');
        $config
            ->expects($this->once())
            ->method('merge')
            ->with([
                'repositories' => [
                    [
                        'type' => 'artifact',
                        'url' => __DIR__.'/../Fixtures/Composer/artifact-data/packages',
                    ],
                ],
            ])
        ;

        $composer = $this->mockComposerWithDataDir($config, $repositoryManager);

        $plugin = new ArtifactsPlugin();
        $plugin->activate($composer, $this->createMock(IOInterface::class));
    }

    public function testDoesNotAddArtifactsRepositoryIfTheDirectoryDoesNotExist(): void
    {
        $repositoryManager = $this->createMock(RepositoryManager::class);
        $repositoryManager
            ->expects($this->never())
            ->method('createRepository')
        ;

        $repositoryManager
            ->expects($this->never())
            ->method('addRepository')
        ;

        $config = $this->mockConfigWithDataDir(__DIR__.'/../Fixtures/Composer/null-data');
        $config
            ->expects($this->never())
            ->method('merge')
        ;

        $composer = $this->mockComposerWithDataDir($config, $repositoryManager);

        $plugin = new ArtifactsPlugin();
        $plugin->activate($composer, $this->createMock(IOInterface::class));
    }

    public function testDoesNotAddArtifactsRepositoryIfItHasNoPackages(): void
    {
        $repository = $this->createMock(RepositoryInterface::class);
        $repository
            ->method('getPackages')
            ->willReturn([])
        ;

        $repositoryManager = $this->createMock(RepositoryManager::class);
        $repositoryManager
            ->expects($this->once())
            ->method('createRepository')
            ->with('artifact', ['url' => __DIR__.'/../Fixtures/Composer/artifact-data/packages'])
            ->willReturn($repository)
        ;

        $repositoryManager
            ->expects($this->never())
            ->method('addRepository')
        ;

        $config = $this->mockConfigWithDataDir(__DIR__.'/../Fixtures/Composer/artifact-data');
        $config
            ->expects($this->never())
            ->method('merge')
        ;

        $composer = $this->mockComposerWithDataDir($config, $repositoryManager);

        $plugin = new ArtifactsPlugin();
        $plugin->activate($composer, $this->createMock(IOInterface::class));
    }

    public function testRegistersProviders(): void
    {
        $config = $this->mockConfigWithDataDir(__DIR__.'/../Fixtures/Composer/provider-data');
        $config
            ->expects($this->exactly(2))
            ->method('merge')
        ;

        $composer = $this->mockComposerWithDataDir(
            $config,
            null,
            [$this->mockPackage('foo/bar', 'contao-provider', __DIR__.'/../Fixtures/Composer/provider-data/packages/foo-bar-1.0.0.zip')],
            ['foo/bar' => '*']
        );

        $plugin = new ArtifactsPlugin();
        $plugin->activate($composer, $this->createMock(IOInterface::class));
    }

    public function testDoesNotRegisterNotRequiredProviders(): void
    {
        $config = $this->mockConfigWithDataDir(__DIR__.'/../Fixtures/Composer/provider-data');
        $config
            ->expects($this->once())
            ->method('merge')
        ;

        $composer = $this->mockComposerWithDataDir(
            $config,
            null,
            [$this->mockPackage('foo/bar', 'contao-provider')]
        );

        $plugin = new ArtifactsPlugin();
        $plugin->activate($composer, $this->createMock(IOInterface::class));
    }

    public function testDoesNotRegisterNonProviderPackages(): void
    {
        $config = $this->mockConfigWithDataDir(__DIR__.'/../Fixtures/Composer/artifact-data');
        $config
            ->expects($this->once())
            ->method('merge')
        ;

        $composer = $this->mockComposerWithDataDir(
            $config,
            null,
            [$this->mockPackage('foo/bar', 'contao-bundle')],
            ['foo/bar' => '*']
        );

        $plugin = new ArtifactsPlugin();
        $plugin->activate($composer, $this->createMock(IOInterface::class));
    }

    public function testDoesNothingWithoutPackagesDir(): void
    {
        $config = $this->mockConfigWithDataDir(__DIR__.'/../Fixtures/Composer/null-data');

        $composer = $this->createMock(Composer::class);
        $composer
            ->method('getConfig')
            ->willReturn($config)
        ;

        $composer
            ->expects($this->never())
            ->method('getRepositoryManager')
        ;

        $plugin = new ArtifactsPlugin();
        $plugin->activate($composer, $this->createMock(IOInterface::class));
    }

    /**
     * @param RepositoryManager|null $repositoryManager
     *
     * @return Composer&MockObject
     */
    private function mockComposerWithDataDir($config, $repositoryManager = null, array $packages = [], $requires = []): Composer
    {
        $rootPackage = $this->createMock(RootPackageInterface::class);
        $rootPackage
            ->expects(empty($requires) ? $this->any() : $this->atLeastOnce())
            ->method('getRequires')
            ->willReturn($requires)
        ;

        if (null === $repositoryManager) {
            $repository = $this->createMock(RepositoryInterface::class);
            $repository
                ->method('getPackages')
                ->willReturn($packages)
            ;

            $repositoryManager = $this->createMock(RepositoryManager::class);
            $repositoryManager
                ->method('createRepository')
                ->willReturn($repository)
            ;
        }

        $composer = $this->createMock(Composer::class);
        $composer
            ->method('getConfig')
            ->willReturn($config)
        ;

        $composer
            ->expects($this->any())
            ->method('getRepositoryManager')
            ->willReturn($repositoryManager)
        ;

        $composer
            ->method('getPackage')
            ->willReturn($rootPackage)
        ;

        return $composer;
    }

    /**
     * @return Config&MockObject
     */
    private function mockConfigWithDataDir(string $dataDir): MockObject
    {
        $config = $this->createMock(Config::class);
        $config
            ->method('get')
            ->with('data-dir')
            ->willReturn($dataDir)
        ;

        return $config;
    }

    /**
     * @return PackageInterface&MockObject
     */
    private function mockPackage(string $name, string $type, string $distUrl = null): PackageInterface
    {
        $package = $this->createMock(PackageInterface::class);
        $package
            ->expects('contao-provider' === $type ? $this->once() : $this->never())
            ->method('getName')
            ->willReturn($name)
        ;

        $package
            ->expects($this->once())
            ->method('getType')
            ->willReturn($type)
        ;

        $package
            ->expects($distUrl ? $this->exactly(2) : $this->never())
            ->method('getDistUrl')
            ->willReturn($distUrl)
        ;

        return $package;
    }
}
