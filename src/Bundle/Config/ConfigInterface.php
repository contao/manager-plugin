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

use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Component\HttpKernel\KernelInterface;

interface ConfigInterface
{
    /**
     * Returns the name.
     *
     * @return string
     */
    public function getName();

    /**
     * Returns the replaces.
     *
     * @return list<string>
     */
    public function getReplace();

    /**
     * Sets the replaces.
     *
     * @param list<string> $replace
     *
     * @return $this
     */
    public function setReplace(array $replace);

    /**
     * Returns the "load after" bundles.
     *
     * @return list<string>
     */
    public function getLoadAfter();

    /**
     * Sets the "load after" bundles.
     *
     * @param list<string> $loadAfter
     *
     * @return $this
     */
    public function setLoadAfter(array $loadAfter);

    /**
     * Returns true if the bundle should be loaded in "prod" environment.
     *
     * @return bool
     */
    public function loadInProduction();

    /**
     * Sets if the bundle should be loaded in "prod" environment.
     *
     * @param bool $loadInProduction
     *
     * @return $this
     */
    public function setLoadInProduction($loadInProduction);

    /**
     * Returns true if the bundle should be loaded in "dev" environment.
     *
     * @return bool
     */
    public function loadInDevelopment();

    /**
     * Sets if the bundle should be loaded in "dev" environment.
     *
     * @param bool $loadInDevelopment
     *
     * @return $this
     */
    public function setLoadInDevelopment($loadInDevelopment);

    /**
     * Returns a bundle instance for this configuration.
     *
     * @return BundleInterface
     */
    public function getBundleInstance(KernelInterface $kernel);
}
