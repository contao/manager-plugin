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

    /**
     * Dumps the Contao Manager plugins.
     *
     * @param Event $event
     */
    public function dumpPlugins(Event $event)
    {
        $composer = $event->getComposer();

        $installer = new Installer();
        $installer->dumpPlugins($composer->getLocker(), $composer->getPackage());

        $event->getIO()->write('Dumped Contao Manager plugins');
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            ScriptEvents::POST_INSTALL_CMD => 'dumpPlugins',
            ScriptEvents::POST_UPDATE_CMD => 'dumpPlugins',
        ];
    }
}
