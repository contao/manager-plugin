<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
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

    public function __construct(string $modulesDir)
    {
        $this->modulesDir = $modulesDir;
    }

    /**
     * {@inheritdoc}
     */
    public function parse($resource, $type = null): array
    {
        if (isset($this->loaded[$resource]) || !is_dir($this->modulesDir.'/'.$resource)) {
            return [];
        }

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
                    if (0 === strncmp($module, '*', 1)) {
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
    public function supports($resource, $type = null): bool
    {
        return 'ini' === $type || is_dir($this->modulesDir.'/'.$resource);
    }

    /**
     * Parses the file and returns the configuration array.
     *
     * @throws \RuntimeException If the file cannot be decoded
     */
    private function parseIniFile(string $file): array
    {
        if (\function_exists('parse_ini_file')) {
            $ini = parse_ini_file($file, true);
        } elseif (\function_exists('parse_ini_string')) {
            $ini = parse_ini_string(file_get_contents($file), true);
        } else {
            throw new \RuntimeException('"parse_ini_file" or "parse_ini_string" is required to load contao-module packages');
        }

        if (!\is_array($ini)) {
            throw new \RuntimeException("File $file cannot be decoded");
        }

        if (!isset($ini['requires']) || !\is_array($ini['requires'])) {
            return [];
        }

        return $ini['requires'];
    }
}
