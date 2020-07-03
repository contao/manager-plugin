<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerPlugin\Tests\Bundle\Config;

use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Config\ConfigResolver;
use Contao\ManagerPlugin\Dependency\UnresolvableDependenciesException;
use PHPUnit\Framework\TestCase;

class ConfigResolverTest extends TestCase
{
    /**
     * @var ConfigResolver
     */
    private $resolver;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->resolver = new ConfigResolver();
    }

    public function testCanBeInstantiated(): void
    {
        $this->assertInstanceOf('Contao\ManagerPlugin\Bundle\Config\ConfigResolver', $this->resolver);
    }

    public function testDefinesAFluentInterface(): void
    {
        $result = $this->resolver->add(new BundleConfig('foobar'));

        $this->assertInstanceOf(ConfigResolver::class, $result);
    }

    /**
     * @dataProvider getBundleConfigs
     */
    public function testAddsTheBundleConfigs(array $configs, array $expectedResult): void
    {
        // Shuffle the input around to ensure the input order does not alter the output.
        // This does not guarantee failure if the random input already provides the expected output but is better
        // than nothing - at least we have a higher probability than 0 to catch these issues here.
         shuffle($configs);
        foreach ($configs as $config) {
            $this->resolver->add($config);
        }

        $actualResult = $this->resolver->getBundleConfigs(false);

        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * @return array<string,BundleConfig[]|array<string,BundleConfig>>
     */
    public function getBundleConfigs(): array
    {
        $config1 = new BundleConfig('name1');
        $config2 = (new BundleConfig('name2'))->setLoadAfter(['name1']);
        $config3 = (new BundleConfig('name3'))->setReplace(['name1', 'name2']);
        $config4 = (new BundleConfig('name4'))->setLoadAfter(['core']);
        $config5 = (new BundleConfig('name5'))->setReplace(['core']);
        $config6 = (new BundleConfig('name6'))->setReplace(['name2']);
        $config7a = new BundleConfig('name7');
        $config7b = (new BundleConfig('name7'))->setReplace(['name2']);
        $config8a = (new BundleConfig('name8'))->setLoadAfter(['name1'])->setReplace(['foo']);
        $config8b = (new BundleConfig('name8'))->setLoadAfter(['name2'])->setReplace(['bar']);

        return [
            'Test default configs' => [
                [
                    $config1,
                ],
                [
                    'name1' => $config1,
                ],
            ],
            'Test load after order' => [
                [
                    $config2,
                    $config1,
                ],
                [
                    'name1' => $config1,
                    'name2' => $config2,
                ],
            ],
            'Test replaces' => [
                [
                    $config1,
                    $config2,
                    $config3,
                ],
                [
                    'name3' => $config3,
                ],
            ],
            'Test load after a module that does not exist but is replaced by new one' => [
                [
                    $config4,
                    $config5,
                ],
                [
                    'name5' => $config5,
                    'name4' => $config4,
                ],
            ],
            'Test replaces config instance if replaced by another config' => [
                [
                    $config1,
                    $config2,
                    $config6,
                ],
                [
                    'name1' => $config1,
                    'name6' => $config6,
                ],
            ],
            'Test latter config overrides previous one with the same name' => [
                [
                    $config2,
                    $config7a,
                    $config7b,
                ],
                [
                    'name7' => $config7b,
                ],
            ],
            'Test latter config merges previous one with the same name' => [
                [
                    $config1,
                    $config2,
                    $config8a,
                    $config8b,
                ],
                [
                    'name1' => $config1,
                    'name2' => $config2,
                    'name8' => (new BundleConfig('name8'))->setLoadAfter(['name1', 'name2'])->setReplace(['foo', 'bar']),
                ],
            ],
        ];
    }

    public function testIgnoresDevelopmentBundlesInProduction(): void
    {
        $this->resolver->add(new BundleConfig('name1'));
        $this->resolver->add((new BundleConfig('name2'))->setLoadInProduction(false));

        $this->assertCount(1, $this->resolver->getBundleConfigs(false));
    }

    public function testIgnoresProductionBundlesInDevelopment(): void
    {
        $this->resolver->add(new BundleConfig('name1'));
        $this->resolver->add((new BundleConfig('name2'))->setLoadInDevelopment(false));

        $this->assertCount(1, $this->resolver->getBundleConfigs(true));
    }

    public function testSupportsUnsettingABundle(): void
    {
        $this->resolver->add(new BundleConfig('name1'));
        $this->resolver->add((new BundleConfig('name1'))->setLoadInProduction(false));

        $this->assertCount(0, $this->resolver->getBundleConfigs(false));
    }

    public function testFailsIfTheDependenciesCannotBeResolved(): void
    {
        $this->resolver
            ->add((new BundleConfig('name1'))->setLoadAfter(['name2']))
            ->add((new BundleConfig('name2'))->setLoadAfter(['name1']))
        ;

        $this->expectException(UnresolvableDependenciesException::class);

        $this->resolver->getBundleConfigs(false);
    }
}
