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
use Symfony\Component\Filesystem\Filesystem;

class ManagerPluginInstaller implements PluginInterface, EventSubscriberInterface
{
    use DependencyResolverTrait;

    /**
     * @var string
     */
    private static $generatedClassTemplate = <<<'PHP'
<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerPlugin;

use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Config\ConfigPluginInterface;
use Contao\ManagerPlugin\Config\ExtensionPluginInterface;
use Contao\ManagerPlugin\Routing\RoutingPluginInterface;

/**
 * This class has been auto-generated. It will be overwritten at every run of
 * "composer install" or "composer update".
 *
 * @see \Contao\ManagerPlugin\Composer\Installer
 */
%s
{
    public const BUNDLE_PLUGINS = BundlePluginInterface::class;
    public const CONFIG_PLUGINS = ConfigPluginInterface::class;
    public const EXTENSION_PLUGINS = ExtensionPluginInterface::class;
    public const ROUTING_PLUGINS = RoutingPluginInterface::class;

    /**
     * @var array
     */
    private $plugins;

    /**
     * @var array
     */
    private $disabled = [];

    public function __construct(string $installedJson = null, array $plugins = null)
    {
        if (null !== $installedJson) {
            @trigger_error('Passing the path to the Composer installed.json as first argument is no longer supported in version 2.3.', E_USER_DEPRECATED);
        }

        $this->plugins = $plugins ?: %s;
    }

    /**
     * Returns all active plugin instances.
     *
     * @return array<string,BundlePluginInterface>
     */
    public function getInstances(): array
    {
        return array_diff_key($this->plugins, array_flip($this->disabled));
    }

    /**
     * Returns the active plugin instances of a given type (see class constants).
     *
     * @return array<string,BundlePluginInterface>
     */
    public function getInstancesOf(string $type, bool $reverseOrder = false): array
    {
        $plugins = array_filter(
            $this->getInstances(),
            function ($plugin) use ($type) {
                return is_a($plugin, $type);
            }
        );

        if ($reverseOrder) {
            $plugins = array_reverse($plugins, true);
        }

        return array_diff_key($plugins, array_flip($this->disabled));
    }

    /**
     * @return string[]
     */
    public function getDisabledPackages(): array
    {
        return $this->disabled;
    }

    public function setDisabledPackages(array $packages): void
    {
        $this->disabled = $packages;
    }
}

PHP;

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
        $io = $event->getIO();

        if (!file_exists(__DIR__.'/../PluginLoader.php')) {
            $io->write('<info>contao/manager-plugin:</info> Class not found (probably scheduled for removal); generation of plugin class skipped.');

            return;
        }

        $io->write('<info>contao/manager-plugin:</info> Generating plugin class...');

        // Require the autoload.php file so the Plugin classes are loaded
        require $event->getComposer()->getConfig()->get('vendor-dir').'/autoload.php';

        $this->doDumpPlugins($event->getComposer()->getRepositoryManager()->getLocalRepository(), $io);
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
            $load[] = "            '$package' => new \\$class()";
        }

        // Dump empty list if there are no packages
        if (empty($load)) {
            $pluginList = '[]';
        } else {
            $pluginList = sprintf("[\n%s,\n        ]", implode(",\n", $load));
        }

        $content = sprintf(
            static::$generatedClassTemplate,
            'cla'.'ss '.'PluginLoader', // workaround for regex-based code parsers :-(
            $pluginList
        );

        $this->filesystem->dumpFile(__DIR__.'/../PluginLoader.php', $content);
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
