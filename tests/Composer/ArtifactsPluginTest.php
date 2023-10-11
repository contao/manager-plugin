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
use Composer\Package\Link;
use Composer\Package\PackageInterface;
use Composer\Package\RootPackageInterface;
use Composer\Plugin\PluginEvents;
use Composer\Plugin\PreCommandRunEvent;
use Composer\Repository\RepositoryInterface;
use Composer\Repository\RepositoryManager;
use Composer\Semver\Constraint\Constraint;
use Contao\ManagerPlugin\Composer\ArtifactsPlugin;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\Input;

class ArtifactsPluginTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        putenv('COMPOSER=');
    }

    public function testAddsArtifactsRepositoryFromComposerDir(): void
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
            ->with('artifact', ['url' => __DIR__.'/../Fixtures/Composer/artifact-data/contao-manager/packages'])
            ->willReturn($repository)
        ;

        $repositoryManager
            ->expects($this->once())
            ->method('addRepository')
            ->with($repository)
        ;

        putenv('COMPOSER='.__DIR__.'/../Fixtures/Composer/artifact-data/composer.json');

        $config = $this->mockConfig(null);
        $config
            ->expects($this->once())
            ->method('merge')
            ->with([
                'repositories' => [
                    [
                        'type' => 'artifact',
                        'url' => __DIR__.'/../Fixtures/Composer/artifact-data/contao-manager/packages',
                    ],
                ],
            ])
        ;

        $composer = $this->mockComposerWithDataDir($config, $repositoryManager);

        $plugin = new ArtifactsPlugin();
        $plugin->activate($composer, $this->createMock(IOInterface::class));
    }

    public function testAddsArtifactsRepositoryFromDataDir(): void
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
            ->with('artifact', ['url' => __DIR__.'/../Fixtures/Composer/artifact-data/contao-manager/packages'])
            ->willReturn($repository)
        ;

        $repositoryManager
            ->expects($this->once())
            ->method('addRepository')
            ->with($repository)
        ;

        $config = $this->mockConfig(__DIR__.'/../Fixtures/Composer/artifact-data/contao-manager');
        $config
            ->expects($this->once())
            ->method('merge')
            ->with([
                'repositories' => [
                    [
                        'type' => 'artifact',
                        'url' => __DIR__.'/../Fixtures/Composer/artifact-data/contao-manager/packages',
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

        putenv('COMPOSER='.__DIR__.'/../Fixtures/Composer/null-data/composer.json');

        $config = $this->mockConfig(__DIR__.'/../Fixtures/Composer/null-data/contao-manager');
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
            ->with('artifact', ['url' => __DIR__.'/../Fixtures/Composer/artifact-data/contao-manager/packages'])
            ->willReturn($repository)
        ;

        $repositoryManager
            ->expects($this->never())
            ->method('addRepository')
        ;

        putenv('COMPOSER='.__DIR__.'/../Fixtures/Composer/artifact-data/composer.json');

        $config = $this->mockConfig(__DIR__.'/../Fixtures/Composer/artifact-data/contao-manager');
        $config
            ->expects($this->never())
            ->method('merge')
        ;

        $composer = $this->mockComposerWithDataDir($config, $repositoryManager);

        $plugin = new ArtifactsPlugin();
        $plugin->activate($composer, $this->createMock(IOInterface::class));
    }

    public function testRegistersContaoProviders(): void
    {
        putenv('COMPOSER='.__DIR__.'/../Fixtures/Composer/artifact-data/composer.json');

        $repositories = [
            ['type' => 'artifact', 'url' => __DIR__.'/../Fixtures/Composer/artifact-data/contao-manager/packages'],
            ['type' => 'vcs', 'url' => 'https://example.org/'],
        ];

        $config = $this->mockConfig(null);

        $config
            ->expects($this->exactly(2))
            ->method('merge')
            ->withConsecutive(
                ...array_map(
                    static function (array $repository) {
                        return [['repositories' => [$repository]]];
                    },
                    $repositories
                )
            )
        ;

        $config
            ->expects($this->once())
            ->method('getRepositories')
            ->willReturn($repositories)
        ;

        $composer = $this->mockComposerWithDataDir(
            $config,
            null,
            [
                $this->mockPackage(
                    'foo/bar',
                    'contao-provider',
                    '1.0.0',
                    __DIR__.'/../Fixtures/Composer/provider-data/contao-manager/packages/foo-bar-1.0.0.zip'
                ),
            ],
            ['foo/bar' => true]
        );

        /** @var PackageInterface|MockObject $rootPackage */
        $rootPackage = $composer->getPackage();
        $rootPackage
            ->expects($this->once())
            ->method('setRepositories')
        ;

        $plugin = new ArtifactsPlugin();
        $plugin->activate($composer, $this->createMock(IOInterface::class));
    }

    public function testDoesNotRegisterProvidersThatAreNotRequired(): void
    {
        $config = $this->mockConfig(__DIR__.'/../Fixtures/Composer/provider-data/contao-manager');
        $config
            ->expects($this->once())
            ->method('merge')
        ;

        $composer = $this->mockComposerWithDataDir(
            $config,
            null,
            [$this->mockPackage('foo/bar', 'contao-provider', null, null, false)]
        );

        /** @var PackageInterface|MockObject $rootPackage */
        $rootPackage = $composer->getPackage();
        $rootPackage
            ->expects($this->never())
            ->method('setRepositories')
        ;

        $plugin = new ArtifactsPlugin();
        $plugin->activate($composer, $this->createMock(IOInterface::class));
    }

    public function testDoesNotRegisterProvidersWhereConstraintDoesNotMatch(): void
    {
        $config = $this->mockConfig(__DIR__.'/../Fixtures/Composer/provider-data/contao-manager');
        $config
            ->expects($this->once())
            ->method('merge')
        ;

        $composer = $this->mockComposerWithDataDir(
            $config,
            null,
            [$this->mockPackage('foo/bar', 'contao-provider', '1.0.0')],
            ['foo/bar' => false]
        );

        /** @var PackageInterface|MockObject $rootPackage */
        $rootPackage = $composer->getPackage();
        $rootPackage
            ->expects($this->never())
            ->method('setRepositories')
        ;

        $plugin = new ArtifactsPlugin();
        $plugin->activate($composer, $this->createMock(IOInterface::class));
    }

    public function testDoesNotRegisterPackagesThatAreNotProviders(): void
    {
        $config = $this->mockConfig(__DIR__.'/../Fixtures/Composer/artifact-data/contao-manager');
        $config
            ->expects($this->once())
            ->method('merge')
        ;

        $composer = $this->mockComposerWithDataDir(
            $config,
            null,
            [$this->mockPackage('foo/bar', 'contao-bundle', null, null, false)]
        );

        /** @var PackageInterface|MockObject $rootPackage */
        $rootPackage = $composer->getPackage();
        $rootPackage
            ->expects($this->never())
            ->method('setRepositories')
        ;

        $plugin = new ArtifactsPlugin();
        $plugin->activate($composer, $this->createMock(IOInterface::class));
    }

    public function testDoesNotRegisterDuplicateRepositories(): void
    {
        $config = $this->mockConfig(__DIR__.'/../Fixtures/Composer/provider-data/contao-manager');
        $config
            ->expects($this->exactly(2))
            ->method('merge')
            ->withConsecutive(
                [$this->arrayHasKey('repositories')],
                [
                    $this->logicalAnd(
                        $this->arrayHasKey('repositories'),
                        $this->equalTo([
                            'repositories' => [[
                                'type' => 'vcs',
                                'url' => 'https://example.org/',
                            ]],
                        ])
                    ),
                ]
            )
        ;

        $config
            ->expects($this->once())
            ->method('getRepositories')
            ->willReturn(['foo' => 'bar'])
        ;

        $composer = $this->mockComposerWithDataDir(
            $config,
            null,
            [
                $this->mockPackage(
                    'foo/current-provider',
                    'contao-provider',
                    '1.0.0',
                    __DIR__.'/../Fixtures/Composer/provider-data/contao-manager/packages/foo-bar-1.0.0.zip'
                ),
                $this->mockPackage(
                    'foo/new-provider',
                    'contao-provider',
                    '1.0.0',
                    __DIR__.'/../Fixtures/Composer/provider-data/contao-manager/packages/foo-bar-1.0.0.zip'
                ),
            ],
            ['foo/current-provider' => true, 'foo/new-provider' => true]
        );

        /** @var PackageInterface|MockObject $rootPackage */
        $rootPackage = $composer->getPackage();
        $rootPackage
            ->expects($this->once())
            ->method('setRepositories')
            ->with(['foo' => 'bar'])
        ;

        /** @var RepositoryManager|MockObject $repositoryManager */
        $repositoryManager = $composer->getRepositoryManager();
        $repositoryManager
            ->expects($this->exactly(2))
            ->method('createRepository')
        ;

        $repositoryManager
            ->expects($this->exactly(2))
            ->method('addRepository')
        ;

        $plugin = new ArtifactsPlugin();
        $plugin->activate($composer, $this->createMock(IOInterface::class));
    }

    public function testCorrectlyHandlesMultiplePackagesAndProviders(): void
    {
        $repositories = [
            ['type' => 'artifact', 'url' => __DIR__.'/../Fixtures/Composer/provider-data/contao-manager/packages'],
            ['type' => 'vcs', 'url' => 'https://example.org/'],
        ];

        $config = $this->mockConfig(__DIR__.'/../Fixtures/Composer/provider-data/contao-manager');
        $config
            ->expects($this->exactly(2))
            ->method('merge')
            ->withConsecutive(
                ...array_map(
                    static function (array $repository) {
                        return [['repositories' => [$repository]]];
                    },
                    $repositories
                )
            )
        ;

        $config
            ->expects($this->once())
            ->method('getRepositories')
            ->willReturn($repositories)
        ;

        $config
            ->expects($this->once())
            ->method('getRepositories')
            ->willReturn([])
        ;

        $composer = $this->mockComposerWithDataDir(
            $config,
            null,
            [
                $this->mockPackage(
                    'foo/current-provider',
                    'contao-provider',
                    '1.2.0',
                    __DIR__.'/../Fixtures/Composer/provider-data/contao-manager/packages/foo-bar-1.0.0.zip'
                ),
                $this->mockPackage('foo/old-provider', 'contao-provider', '1.0.0'),
                $this->mockPackage('foo/bar', 'contao-bundle', null, null, false),
            ],
            ['foo/current-provider' => true, 'foo/old-provider' => false]
        );

        /** @var PackageInterface|MockObject $rootPackage */
        $rootPackage = $composer->getPackage();
        $rootPackage
            ->expects($this->once())
            ->method('setRepositories')
        ;

        $plugin = new ArtifactsPlugin();
        $plugin->activate($composer, $this->createMock(IOInterface::class));
    }

    public function testDoesNothingWithoutPackagesDir(): void
    {
        $config = $this->mockConfig(__DIR__.'/../Fixtures/Composer/null-data');

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

    public function testImplementsTheAPI2Methods(): void
    {
        $plugin = new ArtifactsPlugin();
        $composer = $this->createMock(Composer::class);
        $io = $this->createMock(IOInterface::class);

        $this->assertNull($plugin->deactivate($composer, $io));
        $this->assertNull($plugin->uninstall($composer, $io));
    }

    public function testRegistersPreCommandeEvent(): void
    {
        $this->assertSame([PluginEvents::PRE_COMMAND_RUN => ['preCommandRun', 1]], ArtifactsPlugin::getSubscribedEvents());
    }

    public function testRegistersContaoProvidersFromRequireCommand(): void
    {
        putenv('COMPOSER='.__DIR__.'/../Fixtures/Composer/artifact-data/composer.json');

        $repositories = [
            ['type' => 'artifact', 'url' => __DIR__.'/../Fixtures/Composer/artifact-data/contao-manager/packages'],
            ['type' => 'vcs', 'url' => 'https://example.org/'],
        ];

        $config = $this->mockConfig(null);
        $config
            ->expects($this->exactly(2))
            ->method('merge')
            ->withConsecutive(
                ...array_map(
                    static function (array $repository) {
                        return [['repositories' => [$repository]]];
                    },
                    $repositories
                )
            )
        ;

        $config
            ->expects($this->once())
            ->method('getRepositories')
            ->willReturn($repositories)
        ;

        $composer = $this->mockComposerWithDataDir(
            $config,
            null,
            [
                $this->mockPackage(
                    'foo/bar',
                    'contao-provider',
                    '1.0.0',
                    __DIR__.'/../Fixtures/Composer/provider-data/contao-manager/packages/foo-bar-1.0.0.zip'
                ),
            ]
        );

        /** @var PackageInterface|MockObject $rootPackage */
        $rootPackage = $composer->getPackage();
        $rootPackage
            ->expects($this->once())
            ->method('setRepositories')
        ;

        $plugin = new ArtifactsPlugin();
        $plugin->activate($composer, $this->createMock(IOInterface::class));

        $input = $this->createMock(Input::class);
        $input
            ->expects($this->once())
            ->method('getArgument')
            ->with('packages')
            ->willReturn(['foo/bar'])
        ;

        $plugin->preCommandRun(new PreCommandRunEvent('pre-command-run', $input, 'require'));
    }

    /**
     * @param Config&MockObject            $config
     * @param RepositoryManager&MockObject $repositoryManager
     *
     * @return Composer&MockObject
     */
    private function mockComposerWithDataDir(Config $config, RepositoryManager $repositoryManager = null, array $packages = [], array $requires = []): Composer
    {
        $requires = array_map(
            function ($matches) {
                $constraint = $this->createMock(Constraint::class);
                $constraint
                    ->expects($this->once())
                    ->method('matches')
                    ->willReturn($matches)
                ;

                $link = $this->createMock(Link::class);
                $link
                    ->expects($this->once())
                    ->method('getConstraint')
                    ->willReturn($constraint)
                ;

                return $link;
            },
            $requires
        );

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
    private function mockConfig(?string $dataDir): MockObject
    {
        $config = $this->createMock(Config::class);

        if (null === $dataDir) {
            $config
                ->expects($this->never())
                ->method('get')
                ->with('data-dir')
            ;
        } else {
            $config
                ->method('get')
                ->with('data-dir')
                ->willReturn($dataDir)
            ;
        }

        return $config;
    }

    /**
     * @return PackageInterface&MockObject
     */
    private function mockPackage(string $name, string $type, string $version = null, string $distUrl = null, bool $required = true): PackageInterface
    {
        $package = $this->createMock(PackageInterface::class);
        $package
            ->expects($this->atLeastOnce())
            ->method('getName')
            ->willReturn($name)
        ;

        $package
            ->expects($required ? $this->once() : $this->never())
            ->method('getType')
            ->willReturn($type)
        ;

        $package
            ->expects(null === $version ? $this->never() : $this->once())
            ->method('getVersion')
            ->willReturn((string) $version)
        ;

        $package
            ->expects($distUrl ? $this->once() : $this->never())
            ->method('getDistUrl')
            ->willReturn($distUrl)
        ;

        return $package;
    }
}
