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
use Composer\Factory;
use Composer\IO\IOInterface;
use Composer\Json\JsonFile;
use Composer\Package\Version\VersionParser;
use Composer\Plugin\PluginEvents;
use Composer\Plugin\PluginInterface;
use Composer\Plugin\PreCommandRunEvent;
use Composer\Repository\ArtifactRepository;
use Composer\Repository\RepositoryInterface;

class ArtifactsPlugin implements PluginInterface, EventSubscriberInterface
{
    /**
     * @var Composer
     */
    private $composer;

    /**
     * @var RepositoryInterface|null
     */
    private $artifacts;

    public function activate(Composer $composer, IOInterface $io): void
    {
        $this->composer = $composer;

        $packagesDir = \dirname(Factory::getComposerFile()).'/contao-manager/packages';

        if (!is_dir($packagesDir)) {
            $packagesDir = $composer->getConfig()->get('data-dir').'/packages';
        }

        if (!is_dir($packagesDir) || !class_exists(\ZipArchive::class)) {
            return;
        }

        $this->artifacts = $this->addArtifactRepository($composer, $packagesDir);

        if (null === $this->artifacts) {
            return;
        }

        $requires = [];

        foreach ($composer->getPackage()->getRequires() as $name => $link) {
            $requires[$name] = $link->getConstraint();
        }

        $this->registerProviders($requires);
    }

    public function deactivate(Composer $composer, IOInterface $io): void
    {
        // Nothing to do here
    }

    public function uninstall(Composer $composer, IOInterface $io): void
    {
        // Nothing to do here
    }

    public function preCommandRun(PreCommandRunEvent $event): void
    {
        if (!$this->composer || !$this->artifacts || 'require' !== $event->getCommand()) {
            return;
        }

        $versionParser = new VersionParser();
        $requirements = $versionParser->parseNameVersionPairs($event->getInput()->getArgument('packages'));

        $requires = [];

        foreach ($requirements as $requirement) {
            $requires[$requirement['name']] = $versionParser->parseConstraints($requirement['version'] ?? '*');
        }

        if ([] === $requires) {
            return;
        }

        $this->registerProviders($requires);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            PluginEvents::PRE_COMMAND_RUN => ['preCommandRun', 1],
        ];
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

    private function registerProviders(array $requires): void
    {
        $versionParser = new VersionParser();
        $repositoryManager = $this->composer->getRepositoryManager();
        $repositories = [];

        foreach ($this->artifacts->getPackages() as $package) {
            $name = $package->getName();

            if (!\array_key_exists($name, $requires) || 'contao-provider' !== $package->getType()) {
                continue;
            }

            $constraint = $requires[$name];
            $version = $versionParser->parseConstraints($package->getVersion());

            if (null === $constraint || !$constraint->matches($version)) {
                continue;
            }

            $data = $this->getComposerInformation($package->getDistUrl());

            if (null !== $data && isset($data['repositories']) && \is_array($data['repositories'])) {
                foreach ($data['repositories'] as $config) {
                    ksort($config);
                    $repositories[sha1(serialize($config))] = $config;
                }
            }
        }

        if (!empty($repositories)) {
            foreach ($repositories as $config) {
                $repo = $repositoryManager->createRepository($config['type'], $config);
                $repositoryManager->addRepository($repo);
            }

            $this->composer->getConfig()->merge(['repositories' => array_values($repositories)]);
            $this->composer->getPackage()->setRepositories($this->composer->getConfig()->getRepositories());
        }
    }

    /**
     * @see ArtifactRepository::getComposerInformation()
     */
    private function getComposerInformation(string $path): ?array
    {
        $zip = new \ZipArchive();
        $zip->open($path);

        if (!$zip->numFiles) {
            return null;
        }

        $foundFileIndex = $this->locateFile($zip, 'composer.json');

        if (false === $foundFileIndex) {
            return null;
        }

        $configurationFileName = $zip->getNameIndex($foundFileIndex);
        $composerFile = "zip://$path#$configurationFileName";
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
