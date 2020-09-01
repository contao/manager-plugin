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

use Contao\CoreBundle\HttpKernel\Bundle\ContaoModuleBundle;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * @internal
 */
class ModuleConfig extends BundleConfig
{
    public function __construct(string $name)
    {
        parent::__construct($name);

        $this->setLoadAfterLegacyModules();
    }

    /**
     * {@inheritdoc}
     */
    public function getBundleInstance(KernelInterface $kernel)
    {
        $dir = method_exists($kernel, 'getRootDir') ? $kernel->getRootDir() : $kernel->getProjectDir();

        return new ContaoModuleBundle($this->name, $dir);
    }

    /**
     * Adjusts the configuration so the module is loaded after the legacy modules.
     */
    private function setLoadAfterLegacyModules(): void
    {
        static $legacy = [
            'core',
            'calendar',
            'comments',
            'faq',
            'listing',
            'news',
            'newsletter',
        ];

        $modules = array_merge($legacy, [$this->getName()]);
        sort($modules);
        $modules = array_values($modules);
        array_splice($modules, array_search($this->getName(), $modules, true));

        if (!\in_array('core', $modules, true)) {
            $modules[] = 'core';
        }

        $this->setLoadAfter($modules);
    }
}
