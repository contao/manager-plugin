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

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public static function __set_state(array $properties)
    {
        $config = new static($properties['name']);
        $config->setReplace($properties['replace']);
        $config->setLoadAfter($properties['loadAfter']);
        $config->setLoadInProduction($properties['loadInProduction']);
        $config->setLoadInDevelopment($properties['loadInDevelopment']);

        return $config;
    }

    public static function create(string $name): self
    {
        return new static($name);
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function getReplace(): array
    {
        return $this->replace;
    }

    /**
     * {@inheritdoc}
     */
    public function setReplace(array $replace): self
    {
        $this->replace = $replace;
        sort($this->replace);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getLoadAfter(): array
    {
        return $this->loadAfter;
    }

    /**
     * {@inheritdoc}
     */
    public function setLoadAfter(array $loadAfter): self
    {
        $this->loadAfter = $loadAfter;
        sort($this->loadAfter);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function loadInProduction(): bool
    {
        return $this->loadInProduction;
    }

    /**
     * {@inheritdoc}
     */
    public function setLoadInProduction($loadInProduction): self
    {
        $this->loadInProduction = (bool) $loadInProduction;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function loadInDevelopment(): bool
    {
        return $this->loadInDevelopment;
    }

    /**
     * {@inheritdoc}
     */
    public function setLoadInDevelopment($loadInDevelopment): self
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
}
