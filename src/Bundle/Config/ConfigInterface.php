<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2018 Leo Feyer
 *
 * @license LGPL-3.0+
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
     * @return array
     */
    public function getReplace();

    /**
     * Sets the replaces.
     *
     * @param array $replace
     *
     * @return $this
     */
    public function setReplace(array $replace);

    /**
     * Returns the "load after" bundles.
     *
     * @return array
     */
    public function getLoadAfter();

    /**
     * Sets the "load after" bundles.
     *
     * @param array $loadAfter
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
     * @param KernelInterface $kernel
     *
     * @return BundleInterface
     */
    public function getBundleInstance(KernelInterface $kernel);
}
