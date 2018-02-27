<?php

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

class ModuleConfig extends BundleConfig
{
    /**
     * @var array
     */
    private static $legacy = [
        'core',
        'calendar',
        'comments',
        'faq',
        'listing',
        'news',
        'newsletter',
    ];

    /**
     * Constructor.
     *
     * @param string $name
     */
    public function __construct($name)
    {
        parent::__construct($name);

        $this->setLoadAfterLegacyModules();
    }

    /**
     * {@inheritdoc}
     */
    public function getBundleInstance(KernelInterface $kernel)
    {
        return new ContaoModuleBundle($this->name, $kernel->getRootDir());
    }

    /**
     * Adjusts the configuration so the module is loaded after the legacy modules.
     */
    private function setLoadAfterLegacyModules()
    {
        $modules = array_merge(self::$legacy, [$this->getName()]);
        sort($modules);
        $modules = array_values($modules);
        array_splice($modules, array_search($this->getName(), $modules, true));

        if (!\in_array('core', $modules, true)) {
            $modules[] = 'core';
        }

        $this->setLoadAfter($modules);
    }
}
