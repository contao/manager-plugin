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
use Composer\IO\NullIO;
use Composer\Package\CompletePackage;
use Composer\Package\RootPackage;
use Composer\Repository\InstalledArrayRepository;
use Composer\Repository\RepositoryManager;
use Composer\Script\Event;
use Composer\Script\ScriptEvents;
use Contao\ManagerPlugin\Composer\RuntimePlugin;
use PHPUnit\Framework\TestCase;

class RuntimePluginTest extends TestCase
{
    public function testAddsOnlyMissingRuntimeOptions(): void
    {
        $extra = ['unrelated' => true, 'runtime' => ['class' => 'CustomRuntime', 'debug' => false]];
        $composer = $this->createComposer($extra, ['env' => 'prod', 'debug' => true]);
        $plugin = new RuntimePlugin();

        $plugin->configureRuntime(new Event(ScriptEvents::PRE_AUTOLOAD_DUMP, $composer, new NullIO()));

        $this->assertSame([
            'unrelated' => true,
            'runtime' => ['class' => 'CustomRuntime', 'debug' => false, 'env' => 'prod'],
        ], $composer->getPackage()->getExtra());
        $this->assertSame(['runtime' => ['env' => 'prod', 'debug' => true]], $composer->getRepositoryManager()->getLocalRepository()->findPackage('contao/manager-bundle', '*')->getExtra());

        $plugin->configureRuntime(new Event(ScriptEvents::PRE_AUTOLOAD_DUMP, $composer, new NullIO()));

        $this->assertFalse($composer->getPackage()->getExtra()['runtime']['debug']);
    }

    public function testAddsDefaultsWhenNoRuntimeIsConfigured(): void
    {
        $composer = $this->createComposer([], ['env' => 'prod', 'debug' => false]);

        (new RuntimePlugin())->configureRuntime(new Event(ScriptEvents::PRE_AUTOLOAD_DUMP, $composer, new NullIO()));

        $this->assertSame(['runtime' => ['env' => 'prod', 'debug' => false]], $composer->getPackage()->getExtra());
    }

    public function testPreservesExplicitlyDisabledRuntime(): void
    {
        $composer = $this->createComposer(['runtime' => false], ['env' => 'prod']);

        (new RuntimePlugin())->configureRuntime(new Event(ScriptEvents::PRE_AUTOLOAD_DUMP, $composer, new NullIO()));

        $this->assertSame(['runtime' => false], $composer->getPackage()->getExtra());
    }

    public function testDoesNotChangeProjectsWithoutTheManagerBundle(): void
    {
        $composer = $this->createComposer();

        (new RuntimePlugin())->configureRuntime(new Event(ScriptEvents::PRE_AUTOLOAD_DUMP, $composer, new NullIO()));

        $this->assertSame([], $composer->getPackage()->getExtra());
    }

    public function testDoesNotChangeOlderManagerBundlesWithoutRuntimeDefaults(): void
    {
        $composer = $this->createComposer([], []);

        (new RuntimePlugin())->configureRuntime(new Event(ScriptEvents::PRE_AUTOLOAD_DUMP, $composer, new NullIO()));

        $this->assertSame([], $composer->getPackage()->getExtra());
    }

    public function testSubscribesBeforeSymfonyGeneratesTheRuntimeLoader(): void
    {
        $this->assertSame([ScriptEvents::PRE_AUTOLOAD_DUMP => 'configureRuntime'], RuntimePlugin::getSubscribedEvents());
    }

    private function createComposer(array $extra = [], ?array $defaults = null): Composer
    {
        $package = new RootPackage('test/project', '1.0.0', '1.0.0');
        $package->setExtra($extra);
        $packages = [];

        if (null !== $defaults) {
            $managerBundle = new CompletePackage('contao/manager-bundle', '6.0.0', '6.0.0');
            $managerBundle->setExtra(['runtime' => $defaults]);
            $packages[] = $managerBundle;
        }

        $repositoryManager = $this->createStub(RepositoryManager::class);
        $repositoryManager
            ->method('getLocalRepository')
            ->willReturn(new InstalledArrayRepository($packages))
        ;
        $composer = $this->createStub(Composer::class);
        $composer
            ->method('getPackage')
            ->willReturn($package)
        ;
        $composer
            ->method('getRepositoryManager')
            ->willReturn($repositoryManager)
        ;

        return $composer;
    }
}
