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
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;

class ArtifactsPlugin implements PluginInterface
{
    /**
     * {@inheritdoc}
     */
    public function activate(Composer $composer, IOInterface $io): void
    {
        $this->addArtifactRepository($composer);
    }

    private function addArtifactRepository(Composer $composer): void
    {
        $packagesDir = $composer->getConfig()->get('data-dir').'/packages';

        if (is_dir($packagesDir)) {
            $composer->getRepositoryManager()->prependRepository(
                $composer->getRepositoryManager()->createRepository('artifact', ['url' => $packagesDir])
            );
        }
    }
}
