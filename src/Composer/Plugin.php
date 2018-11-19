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

class Plugin implements PluginInterface, EventSubscriberInterface
{
    /**
     * @var Installer
     */
    private $installer;

    public function __construct(Installer $installer = null)
    {
        $this->installer = $installer ?: new Installer();
    }

    /**
     * {@inheritdoc}
     */
    public function activate(Composer $composer, IOInterface $io): void
    {
        $this->addArtifactRepository($composer);
    }

    public function dumpPlugins(Event $event): void
    {
        $io = $event->getIO();

        if (!file_exists(__DIR__.'/../PluginLoader.php')) {
            $io->write('<info>contao/manager-plugin:</info> Class not found (probably scheduled for removal); generation of plugin class skipped.');

            return;
        }

        $io->write('<info>contao/manager-plugin:</info> Generating plugin class...');

        // Require the autoload.php file so the Plugin classes are loaded
        require $event->getComposer()->getConfig()->get('vendor-dir').'/autoload.php';

        $this->installer->dumpPlugins($event->getComposer()->getRepositoryManager()->getLocalRepository(), $io);
        $io->write('<info>contao/manager-plugin:</info> ...done generating plugin class');
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            ScriptEvents::POST_INSTALL_CMD => 'dumpPlugins',
            ScriptEvents::POST_UPDATE_CMD => 'dumpPlugins',
        ];
    }

    private function addArtifactRepository(Composer $composer): void
    {
        $packagesDir = \dirname($composer->getConfig()->get('vendor-dir')).'/contao-manager/packages';

        if (is_dir($packagesDir)) {
            $composer->getRepositoryManager()->prependRepository(
                $composer->getRepositoryManager()->createRepository('artifact', [])
            );
        }
    }
}
