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
use Composer\Config;
use Composer\IO\IOInterface;
use Composer\Json\JsonFile;
use Composer\Package\PackageInterface;
use Composer\Package\RootPackageInterface;
use Composer\Plugin\PluginInterface;
use Composer\Repository\ArtifactRepository;
use Composer\Repository\RepositoryInterface;

class ArtifactsPlugin implements PluginInterface
{
    /**
     * {@inheritdoc}
     */
    public function activate(Composer $composer, IOInterface $io): void
    {
        $packagesDir = $composer->getConfig()->get('data-dir').'/packages';

        if (!is_dir($packagesDir)) {
            return;
        }

        $repository = $this->addArtifactRepository($composer, $packagesDir);

        $this->registerProviders($repository, $composer->getConfig(), $composer->getPackage());
    }

    private function addArtifactRepository(Composer $composer, string $repositoryUrl): RepositoryInterface
    {
        $repository = $composer->getRepositoryManager()->createRepository('artifact', ['url' => $repositoryUrl]);

        $composer->getRepositoryManager()->prependRepository($repository);

        return $repository;
    }

    private function registerProviders(RepositoryInterface $repository, Config $config, RootPackageInterface $rootPackage): void
    {
        $requires = $rootPackage->getRequires();

        foreach ($repository->getPackages() as $package) {
            if ('contao-provider' !== $package->getType() || !\array_key_exists($package->getName(), $requires)) {
                continue;
            }

            $data = $this->getComposerInformation($package);

            if (null !== $data) {
                $config->merge(['repositories' => $data['repositories'] ?? []]);
            }
        }
    }

    /**
     * @see ArtifactRepository::getComposerInformation()
     */
    private function getComposerInformation(PackageInterface $package): ?array
    {
        $zip = new \ZipArchive();
        $zip->open($package->getDistUrl());

        if (0 == $zip->numFiles) {
            return null;
        }

        $foundFileIndex = $this->locateFile($zip, 'composer.json');
        if (false === $foundFileIndex) {
            return null;
        }

        $configurationFileName = $zip->getNameIndex($foundFileIndex);

        $composerFile = "zip://{$package->getDistUrl()}#$configurationFileName";
        $json = file_get_contents($composerFile);

        return JsonFile::parseJson($json, $composerFile);
    }

    /**
     * @see ArtifactRepository::locateFile()
     */
    private function locateFile(\ZipArchive $zip, string $filename)
    {
        $indexOfShortestMatch = false;
        $lengthOfShortestMatch = -1;

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $stat = $zip->statIndex($i);
            if (strcmp(basename($stat['name']), $filename) === 0) {
                $directoryName = dirname($stat['name']);
                if ($directoryName === '.') {
                    //if composer.json is in root directory
                    //it has to be the one to use.
                    return $i;
                }

                if (strpos($directoryName, '\\') !== false ||
                    strpos($directoryName, '/') !== false) {
                    //composer.json files below first directory are rejected
                    continue;
                }

                $length = strlen($stat['name']);
                if ($indexOfShortestMatch === false || $length < $lengthOfShortestMatch) {
                    //Check it's not a directory.
                    $contents = $zip->getFromIndex($i);
                    if ($contents !== false) {
                        $indexOfShortestMatch = $i;
                        $lengthOfShortestMatch = $length;
                    }
                }
            }
        }

        return $indexOfShortestMatch;
    }
}
