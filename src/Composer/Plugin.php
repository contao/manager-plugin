<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\ManagerPlugin\Composer;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event;
use Composer\Script\ScriptEvents;

class Plugin implements PluginInterface, EventSubscriberInterface
{
    /**
     * {@inheritdoc}
     */
    public function activate(Composer $composer, IOInterface $io)
    {
        // Nothing to do here, as all features are provided through event listeners
    }

    public function dumpPluginLoader(Event $event)
    {
        $plugins = [];
        $lockData = $event->getComposer()->getLocker()->getLockData();

        $lockData['packages-dev'] = isset($lockData['packages-dev']) ? $lockData['packages-dev'] : [];

        foreach (array_merge($lockData['packages'], $lockData['packages-dev']) as $package) {
            if (isset($package['extra']['contao-manager-plugin'])) {
//                if (!class_exists($package['extra']['contao-manager-plugin'])) {
//                    throw new \RuntimeException(
//                        sprintf('Plugin class "%s" not found', $package['extra']['contao-manager-plugin'])
//                    );
//                }

                $plugins[$package['name']] = new $package['extra']['contao-manager-plugin']();
            }
        }

        $installer = new Installer();
        $installer->setPlugins($plugins);
        $installer->dumpClass();
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            ScriptEvents::POST_INSTALL_CMD => 'dumpPluginLoader',
            ScriptEvents::POST_UPDATE_CMD  => 'dumpPluginLoader',
        ];
    }
}
