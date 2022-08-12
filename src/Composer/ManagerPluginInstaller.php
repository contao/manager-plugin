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

use App\ContaoManager\Plugin;
use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Package\CompletePackage;
use Composer\Plugin\PluginInterface;
use Composer\Repository\RepositoryInterface;
use Composer\Script\Event;
use Composer\Script\ScriptEvents;
use Contao\ManagerPlugin\Dependency\DependencyResolverTrait;
use Contao\ManagerPlugin\Dependency\DependentPluginInterface;
use Contao\ManagerPlugin\PluginLoader;
use Symfony\Component\Filesystem\Filesystem;

class ManagerPluginInstaller implements PluginInterface, EventSubscriberInterface
{
    use DependencyResolverTrait;

    /**
     * @var Filesystem
     */
    private $filesystem;

    public function __construct(Filesystem $filesystem = null)
    {
        $this->filesystem = $filesystem ?: new Filesystem();
    }

    /**
     * {@inheritdoc}
     */
    public function activate(Composer $composer, IOInterface $io): void
    {
        // Nothing to do here, as all features are provided through event listeners
    }

    public function deactivate(Composer $composer, IOInterface $io): void
    {
        // Nothing to do here, as all features are provided through event listeners
    }

    public function uninstall(Composer $composer, IOInterface $io): void
    {
        // Nothing to do here, as all features are provided through event listeners
    }

    public function dumpPlugins(Event $event): void
    {
        $this->handleUpdateFromLegacyPlugin();

        $io = $event->getIO();
        $io->write('<info>contao/manager-plugin:</info> Dumping generated plugins file...');

        // Require the autoload.php file so the Plugin classes are loaded
        require $event->getComposer()->getConfig()->get('vendor-dir').'/autoload.php';

        $this->doDumpPlugins($event->getComposer()->getRepositoryManager()->getLocalRepository(), $io);

        $io->write('<info>contao/manager-plugin:</info> ...done dumping generated plugins file');
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

    private function handleUpdateFromLegacyPlugin(): void
    {
        $fs = new Filesystem();
        $classPath = __DIR__.'/../PluginLoader.php';
        $resourcePath = __DIR__.'/../Resources/PluginLoader.php';

        // In the old plugin version there were cases where the PluginLoader could not exist
        if ($fs->exists($classPath) && file_get_contents($classPath) === file_get_contents($resourcePath)) {
            return;
        }

        // If the file did not exist at all or the content is not equal, it means we‘re updating from
        // an old version where the PluginLoader class got dynamically replaced. In this case, we have
        // to copy our file again and from that point in time on, things should work just fine.
        $fs->copy($resourcePath, $classPath, true);
    }

    private function doDumpPlugins(RepositoryInterface $repository, IOInterface $io): void
    {
        $plugins = [];

        foreach ($repository->getPackages() as $package) {
            if (!$package instanceof CompletePackage) {
                continue;
            }

            foreach ($this->getPluginClasses($package) as $name => $class) {
                if (!class_exists($class)) {
                    throw new \RuntimeException(sprintf('The plugin class "%s" was not found.', $class));
                }

                if (isset($plugins[$name])) {
                    throw new \RuntimeException(sprintf('The package "%s" cannot be registered twice.', $name));
                }

                $io->write(' - Added plugin for '.$name, true, IOInterface::VERY_VERBOSE);

                $plugins[$name] = new $class();
            }
        }

        $plugins = $this->orderPlugins($plugins);

        // Instantiate a global plugin to load AppBundle or other customizations
        if (class_exists(Plugin::class)) {
            $plugins['app'] = new Plugin();
        } elseif (class_exists(\ContaoManagerPlugin::class)) {
            $plugins['app'] = new \ContaoManagerPlugin();
        }

        $this->dumpClass($plugins);
    }

    private function dumpClass(array $plugins): void
    {
        $load = [];

        foreach ($plugins as $package => $plugin) {
            $class = \get_class($plugin);
            $load[$package] = "#new \\$class()#";
        }

        $content = sprintf(
            "<?php\nreturn %s;",
            str_replace(['\'#', '#\'', '\\\\'], ['', '', '\\'], var_export($load, true))
        );

        $this->filesystem->dumpFile(PluginLoader::getGeneratedPath(), $content);
    }

    /**
     * @return array<string,string>
     */
    private function orderPlugins(array $plugins): array
    {
        $ordered = [];
        $dependencies = [];
        $packages = array_keys($plugins);

        // Load the manager bundle first
        if (isset($plugins['contao/manager-bundle'])) {
            array_unshift($packages, 'contao/manager-bundle');
            $packages = array_unique($packages);
        }

        // Walk through the packages
        foreach ($packages as $packageName) {
            $dependencies[$packageName] = [];

            if ($plugins[$packageName] instanceof DependentPluginInterface) {
                $dependencies[$packageName] = $plugins[$packageName]->getPackageDependencies();
            }
        }

        foreach ($this->orderByDependencies($dependencies) as $packageName) {
            $ordered[$packageName] = $plugins[$packageName];
        }

        return $ordered;
    }

    /**
     * @return array<string,string>
     */
    private function getPluginClasses(CompletePackage $package): array
    {
        $extra = $package->getExtra();

        if (!isset($extra['contao-manager-plugin'])) {
            return [];
        }

        if (\is_string($extra['contao-manager-plugin'])) {
            return [$package->getName() => $extra['contao-manager-plugin']];
        }

        if (\is_array($extra['contao-manager-plugin'])) {
            $replaces = $package->getReplaces();

            foreach (array_keys($extra['contao-manager-plugin']) as $name) {
                if (!isset($replaces[$name]) && $package->getName() !== $name) {
                    throw new \RuntimeException(sprintf('The package "%s" is not replaced by "%s".', $name, $package->getName()));
                }
            }

            return $extra['contao-manager-plugin'];
        }

        throw new \RuntimeException('Invalid value for "extra.contao-manager-plugin".');
    }
}
