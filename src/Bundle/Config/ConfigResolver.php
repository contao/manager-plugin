<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerPlugin\Bundle\Config;

use Contao\ManagerPlugin\Dependency\DependencyResolverTrait;
use UnexpectedValueException;

class ConfigResolver implements ConfigResolverInterface
{
    use DependencyResolverTrait;

    /**
     * @var array<ConfigInterface>
     */
    protected $configs = [];

    /**
     * {@inheritdoc}
     */
    public function add(ConfigInterface $config): self
    {
        $this->configs[] = $config;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getBundleConfigs($development): array
    {
        $bundles = [];

        // Only add bundles which match the environment
        foreach ($this->configs as $config) {
            if (($development && $config->loadInDevelopment()) || (!$development && $config->loadInProduction())) {
                if (null !== $otherConfig = $bundles[$config->getName()] ?? null) {
                    $config = $this->mergeConfig($otherConfig, $config);
                }

                $bundles[$config->getName()] = $config;
            } else {
                unset($bundles[$config->getName()]);
            }
        }

        $loadingOrder = $this->buildLoadingOrder();
        $replaces = $this->buildReplaceMap();
        $normalizedOrder = $this->normalizeLoadingOrder($loadingOrder, $replaces);
        $resolvedOrder = $this->orderByDependencies($normalizedOrder);

        return $this->order($bundles, $resolvedOrder);
    }

    private function mergeConfig(ConfigInterface $otherConfig, ConfigInterface $config): ConfigInterface
    {
        if ($this->configsAreEqual($otherConfig, $config)) {
            return $config;
        }

        if (BundleConfig::class !== \get_class($otherConfig) || BundleConfig::class !== \get_class($config)) {
            throw new UnexpectedValueException(sprintf('Unable to merge "%s" ("%s" with "%s").', $config->getName(), \get_class($otherConfig), \get_class($config)));
        }

        // If both are bundle configs, we have no problem and can merge
        return BundleConfig::create($otherConfig->getName())
            ->setReplace(array_unique(array_merge($otherConfig->getReplace(), $config->getReplace())))
            ->setLoadAfter(array_unique(array_merge($otherConfig->getLoadAfter(), $config->getLoadAfter())))
            ->setLoadInProduction($otherConfig->loadInProduction() || $config->loadInProduction())
            ->setLoadInDevelopment($otherConfig->loadInDevelopment() || $config->loadInDevelopment())
        ;
    }

    private function configsAreEqual(ConfigInterface $otherConfig, ConfigInterface $config): bool
    {
        return
            \get_class($otherConfig) === \get_class($config)
            && $otherConfig->getName() === $config->getName()
            && $otherConfig->getReplace() === $config->getReplace()
            && $otherConfig->getLoadAfter() === $config->getLoadAfter()
            && $otherConfig->loadInProduction() === $config->loadInProduction()
            && $otherConfig->loadInDevelopment() === $config->loadInDevelopment();
    }

    /**
     * @return array<string,string>
     */
    private function buildReplaceMap(): array
    {
        $replace = [];

        foreach ($this->configs as $bundle) {
            $name = $bundle->getName();

            foreach ($bundle->getReplace() as $package) {
                $replace[$package] = $name;
            }
        }

        return $replace;
    }

    /**
     * @return array<string,string>
     */
    private function buildLoadingOrder(): array
    {
        $loadingOrder = [];

        foreach ($this->configs as $bundle) {
            $name = $bundle->getName();

            $loadingOrder[$name] = [];

            foreach ($bundle->getLoadAfter() as $package) {
                $loadingOrder[$name][] = $package;
            }
        }

        uksort(
            $loadingOrder,
            static function (string $a, string $b): int {
                return md5($a) <=> md5($b);
            }
        );

        return $loadingOrder;
    }

    /**
     * @return array<string,string>
     */
    private function order(array $bundles, array $ordered): array
    {
        $return = [];

        foreach ($ordered as $package) {
            if (\array_key_exists($package, $bundles)) {
                $return[$package] = $bundles[$package];
            }
        }

        return $return;
    }

    /**
     * @return array<string,string>
     */
    private function normalizeLoadingOrder(array $loadingOrder, array $replace): array
    {
        foreach ($loadingOrder as $bundleName => &$loadAfter) {
            if (isset($replace[$bundleName])) {
                unset($loadingOrder[$bundleName]);
            } else {
                $this->replaceBundleNames($loadAfter, $replace);
            }
        }

        return $loadingOrder;
    }

    private function replaceBundleNames(array &$loadAfter, array $replace): void
    {
        foreach ($loadAfter as &$bundleName) {
            if (isset($replace[$bundleName])) {
                $bundleName = $replace[$bundleName];
            }
        }
    }
}
