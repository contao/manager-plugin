<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerPlugin\Composer;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event;
use Composer\Script\ScriptEvents;

class RuntimePlugin implements PluginInterface, EventSubscriberInterface
{
    public function activate(Composer $composer, IOInterface $io): void
    {
        // Nothing to do here, as the defaults are supplied before autoload generation
    }

    public function deactivate(Composer $composer, IOInterface $io): void
    {
        // Nothing to do here
    }

    public function uninstall(Composer $composer, IOInterface $io): void
    {
        // Nothing to do here
    }

    public function configureRuntime(Event $event): void
    {
        $composer = $event->getComposer();
        $managerBundle = $composer->getRepositoryManager()->getLocalRepository()->findPackage('contao/manager-bundle', '*');
        $defaults = null === $managerBundle ? [] : ($managerBundle->getExtra()['runtime'] ?? []);

        if (!$defaults) {
            return;
        }

        $package = $composer->getPackage();
        $extra = $package->getExtra();

        if (false === ($extra['runtime'] ?? [])) {
            return;
        }

        // Project runtime options take precedence over the manager bundle defaults.
        // Keep Contao's environment selection independent of Symfony Runtime defaults.
        $extra['runtime'] = ($extra['runtime'] ?? []) + $defaults;
        $package->setExtra($extra);
    }

    public static function getSubscribedEvents(): array
    {
        return [ScriptEvents::PRE_AUTOLOAD_DUMP => 'configureRuntime'];
    }
}
