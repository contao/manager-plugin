<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\ManagerPlugin\Bundle\Parser;

use Contao\ManagerPlugin\Bundle\Config\ModuleConfig;

class IniParser implements ParserInterface
{
    /**
     * @var array
     */
    private $loaded = [];

    /**
     * @var string
     */
    private $modulesDir;

    /**
     * Constructor.
     *
     * @param string $modulesDir
     */
    public function __construct($modulesDir)
    {
        $this->modulesDir = $modulesDir;
    }

    /**
     * {@inheritdoc}
     */
    public function parse($resource, $type = null)
    {
        $configs = [];
        $config = new ModuleConfig($resource);
        $configs[] = $config;

        $this->loaded[$resource] = true;

        $path = $this->modulesDir.'/'.$resource.'/config/autoload.ini';

        if (file_exists($path)) {
            $requires = $this->parseIniFile($path);

            if (0 !== \count($requires)) {
                // Recursively load all modules that are required by other modules
                foreach ($requires as &$module) {
                    if (0 === strpos($module, '*')) {
                        $module = substr($module, 1);

                        // Do not add optional modules that are not installed
                        if (!is_dir($this->modulesDir.'/'.$module)) {
                            continue;
                        }
                    }

                    if (!isset($this->loaded[$module])) {
                        $configs = array_merge($configs, $this->parse($module));
                    }
                }

                unset($module);

                $config->setLoadAfter($requires);
            }
        }

        return $configs;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($resource, $type = null)
    {
        return 'ini' === $type || is_dir($this->modulesDir.'/'.(string) $resource);
    }

    /**
     * Parses the file and returns the configuration array.
     *
     * @param string $file The file path
     *
     * @throws \RuntimeException If the file cannot be decoded
     *
     * @return array The configuration array
     */
    private function parseIniFile($file)
    {
        $ini = parse_ini_file($file, true);

        if (!\is_array($ini)) {
            throw new \RuntimeException("File $file cannot be decoded");
        }

        if (!isset($ini['requires']) || !\is_array($ini['requires'])) {
            return [];
        }

        return $ini['requires'];
    }
}
