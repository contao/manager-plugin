<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\ManagerPlugin\Tests\Bundle\Parser;

use Contao\ManagerPlugin\Bundle\Config\ConfigInterface;
use Contao\ManagerPlugin\Bundle\Parser\JsonParser;
use PHPUnit\Framework\TestCase;

class JsonParserTest extends TestCase
{
    /**
     * @var JsonParser
     */
    private $parser;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->parser = new JsonParser();
    }

    public function testCanBeInstantiated()
    {
        $this->assertInstanceOf('Contao\ManagerPlugin\Bundle\Parser\JsonParser', $this->parser);
        $this->assertInstanceOf('Contao\ManagerPlugin\Bundle\Parser\ParserInterface', $this->parser);
    }

    public function testSupportsJsonFiles()
    {
        $this->assertTrue($this->parser->supports('foobar.json', 'json'));
        $this->assertTrue($this->parser->supports('foobar.json', null));
        $this->assertFalse($this->parser->supports([]));
        $this->assertFalse($this->parser->supports('foobar'));
    }

    public function testParsesSimpleObjects()
    {
        $configs = $this->parser->parse(__DIR__.'/../../Fixtures/Bundle/JsonParser/simple-object.json');

        $this->assertCount(1, $configs);

        /** @var ConfigInterface $config */
        $config = reset($configs);

        $this->assertInstanceOf(ConfigInterface::class, $config);
        $this->assertSame('Contao\CoreBundle\ContaoCoreBundle', $config->getName());
        $this->assertSame([], $config->getReplace());
        $this->assertTrue($config->loadInProduction());
        $this->assertTrue($config->loadInDevelopment());
        $this->assertSame([], $config->getLoadAfter());
    }

    public function testParsesSimpleStrings()
    {
        $configs = $this->parser->parse(__DIR__.'/../../Fixtures/Bundle/JsonParser/simple-string.json');

        $this->assertCount(1, $configs);

        /** @var ConfigInterface $config */
        $config = reset($configs);

        $this->assertInstanceOf(ConfigInterface::class, $config);
        $this->assertSame('Contao\CoreBundle\ContaoCoreBundle', $config->getName());
        $this->assertSame([], $config->getReplace());
        $this->assertTrue($config->loadInProduction());
        $this->assertTrue($config->loadInDevelopment());
        $this->assertSame([], $config->getLoadAfter());
    }

    public function testParsesTheBundleEnvironment()
    {
        /** @var ConfigInterface[] $configs */
        $configs = $this->parser->parse(__DIR__.'/../../Fixtures/Bundle/JsonParser/dev-prod.json');

        $this->assertCount(3, $configs);

        $this->assertInstanceOf(ConfigInterface::class, $configs[0]);
        $this->assertTrue($configs[0]->loadInProduction());
        $this->assertTrue($configs[0]->loadInDevelopment());

        $this->assertInstanceOf(ConfigInterface::class, $configs[1]);
        $this->assertFalse($configs[1]->loadInProduction());
        $this->assertTrue($configs[1]->loadInDevelopment());

        $this->assertInstanceOf(ConfigInterface::class, $configs[2]);
        $this->assertTrue($configs[2]->loadInProduction());
        $this->assertFalse($configs[2]->loadInDevelopment());
    }

    public function testParsesOptionalBundles()
    {
        /** @var ConfigInterface[] $configs */
        $configs = $this->parser->parse(__DIR__.'/../../Fixtures/Bundle/JsonParser/optional.json');

        $this->assertCount(2, $configs);

        $this->assertInstanceOf(ConfigInterface::class, $configs[1]);
        $this->assertSame('Contao\CoreBundle\ContaoCoreBundle', $configs[1]->getName());
        $this->assertSame(['core'], $configs[1]->getReplace());
        $this->assertTrue($configs[1]->loadInProduction());
        $this->assertTrue($configs[1]->loadInDevelopment());
        $this->assertSame(['Foo\BarBundle\FooBarBundle'], $configs[1]->getLoadAfter());
    }

    public function testFailsToParseAFileWithNoBundles()
    {
        $this->expectException('RuntimeException');

        $this->parser->parse(__DIR__.'/../../Fixtures/Bundle/JsonParser/no-bundle.json');
    }

    public function testFailsToParseAMissingFile()
    {
        $this->expectException('InvalidArgumentException');

        $this->parser->parse(__DIR__.'/../../Fixtures/Bundle/JsonParser/missing.json');
    }

    public function testFailsToParseInvalidJsonData()
    {
        $this->expectException('RuntimeException');

        $this->parser->parse(__DIR__.'/../../Fixtures/Bundle/JsonParser/invalid.json');
    }
}
