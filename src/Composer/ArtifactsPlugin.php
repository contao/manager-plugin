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
use Composer\Json\JsonFile;
use Composer\Package\PackageInterface;
use Composer\Plugin\PluginInterface;
use Composer\Repository\ArtifactRepository;
use Composer\Repository\RepositoryInterface;
use Composer\Semver\Constraint\Constraint;

class ArtifactsPlugin implements PluginInterface
{
    /**
     * {@inheritdoc}
     */
    public function activate(Composer $composer, IOInterface $io): void
    {
        $packagesDir = $composer->getConfig()->get('data-dir').'/packages';

        if (!is_dir($packagesDir) || !class_exists(\ZipArchive::class)) {
            return;
        }

        $repository = $this->addArtifactRepository($composer, $packagesDir);

        if (null === $repository) {
            return;
        }

        $this->registerProviders($repository, $composer);
    }

    private function addArtifactRepository(Composer $composer, string $repositoryUrl): ?RepositoryInterface
    {
        $repository = $composer->getRepositoryManager()->createRepository('artifact', ['url' => $repositoryUrl]);

        if (empty($repository->getPackages())) {
            return null;
        }

        $composer->getRepositoryManager()->addRepository($repository);
        $composer->getConfig()->merge(['repositories' => [['type' => 'artifact', 'url' => $repositoryUrl]]]);

        return $repository;
    }

    private function registerProviders(RepositoryInterface $artifacts, Composer $composer): void
    {
        $requires = $composer->getPackage()->getRequires();

        foreach ($artifacts->getPackages() as $package) {
            if ('contao-provider' !== $package->getType() || !\array_key_exists($package->getName(), $requires)) {
                continue;
            }

            $constraint = $requires[$package->getName()]->getConstraint();
            $version = new Constraint(Constraint::OP_EQ, $package->getVersion());

            if (null === $constraint || !$constraint->matches($version)) {
                continue;
            }

            $data = $this->getComposerInformation($package);

            if (null !== $data && isset($data['repositories']) && \is_array($data['repositories'])) {
                $rm = $composer->getRepositoryManager();

                foreach ($data['repositories'] as $config) {
                    $repo = $rm->createRepository($config['type'], $config);
                    $rm->addRepository($repo);
                }

                $composer->getConfig()->merge(['repositories' => $data['repositories'] ?? []]);
            }
        }

        $composer->getPackage()->setRepositories($composer->getConfig()->getRepositories());
    }

    /**
     * @see ArtifactRepository::getComposerInformation()
     */
    private function getComposerInformation(PackageInterface $package): ?array
    {
        $zip = new \ZipArchive();
        $zip->open($package->getDistUrl());

        if (!$zip->numFiles) {
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

        for ($i = 0; $i < $zip->numFiles; ++$i) {
            $stat = $zip->statIndex($i);

            if (0 === strcmp(basename($stat['name']), $filename)) {
                $directoryName = \dirname($stat['name']);

                if ('.' === $directoryName) {
                    // If composer.json is in root directory, it has to be the one to use
                    return $i;
                }

                if (false !== strpos($directoryName, '\\') || false !== strpos($directoryName, '/')) {
                    // composer.json files below first directory are rejected
                    continue;
                }

                $length = \strlen($stat['name']);

                if (false === $indexOfShortestMatch || $length < $lengthOfShortestMatch) {
                    // Check it's not a directory
                    $contents = $zip->getFromIndex($i);

                    if (false !== $contents) {
                        $indexOfShortestMatch = $i;
                        $lengthOfShortestMatch = $length;
                    }
                }
            }
        }

        return $indexOfShortestMatch;
    }
}
