<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\ManagerPlugin\Bundle\Config;

use Symfony\Component\HttpKernel\KernelInterface;

class BundleConfig implements ConfigInterface
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var array
     */
    protected $replace = [];

    /**
     * @var array
     */
    protected $loadAfter = [];

    /**
     * @var bool
     */
    protected $loadInProduction = true;

    /**
     * @var bool
     */
    protected $loadInDevelopment = true;

    /**
     * @param string $name
     */
    public function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function getReplace()
    {
        return $this->replace;
    }

    /**
     * {@inheritdoc}
     */
    public function setReplace(array $replace)
    {
        $this->replace = $replace;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getLoadAfter()
    {
        return $this->loadAfter;
    }

    /**
     * {@inheritdoc}
     */
    public function setLoadAfter(array $loadAfter)
    {
        $this->loadAfter = $loadAfter;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function loadInProduction()
    {
        return $this->loadInProduction;
    }

    /**
     * {@inheritdoc}
     */
    public function setLoadInProduction($loadInProduction)
    {
        $this->loadInProduction = (bool) $loadInProduction;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function loadInDevelopment()
    {
        return $this->loadInDevelopment;
    }

    /**
     * {@inheritdoc}
     */
    public function setLoadInDevelopment($loadInDevelopment)
    {
        $this->loadInDevelopment = (bool) $loadInDevelopment;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getBundleInstance(KernelInterface $kernel)
    {
        if (!class_exists($this->name)) {
            throw new \LogicException(sprintf('The Symfony bundle "%s" does not exist.', $this->name));
        }

        return new $this->name();
    }

    /**
     * Creates a new config instance.
     *
     * @param string $name
     *
     * @return static
     */
    public static function create($name)
    {
        return new static($name);
    }

    /**
     * Create a new config instance from properties.
     *
     * @param array $properties
     *
     * @return static
     */
    public static function __set_state(array $properties)
    {
        $config = new static($properties['name']);
        $config->setReplace($properties['replace']);
        $config->setLoadAfter($properties['loadAfter']);
        $config->setLoadInProduction($properties['loadInProduction']);
        $config->setLoadInDevelopment($properties['loadInDevelopment']);

        return $config;
    }
}
