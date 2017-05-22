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
use Symfony\Component\Finder\SplFileInfo;

class JsonParserTest extends \PHPUnit_Framework_TestCase
{
    const FIXTURES_DIR = '/../../Fixtures/Bundle/JsonParser';

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

    public function testInstantiation()
    {
        $this->assertInstanceOf('Contao\ManagerPlugin\Bundle\Parser\JsonParser', $this->parser);
        $this->assertInstanceOf('Contao\ManagerPlugin\Bundle\Parser\ParserInterface', $this->parser);
    }

    public function testSupports()
    {
        $this->assertTrue($this->parser->supports('foobar.json', 'json'));
        $this->assertTrue($this->parser->supports('foobar.json', null));
        $this->assertFalse($this->parser->supports([]));
        $this->assertFalse($this->parser->supports('foobar'));
    }

    public function testParseSimpleObject()
    {
        $configs = $this->parser->parse(__DIR__.self::FIXTURES_DIR.'/simple-object.json');

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

    public function testParseSimpleString()
    {
        $configs = $this->parser->parse(__DIR__.self::FIXTURES_DIR.'/simple-string.json');

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

    public function testParseDevelopmentAndProduction()
    {
        /** @var ConfigInterface[] $configs */
        $configs = $this->parser->parse(__DIR__.self::FIXTURES_DIR.'/dev-prod.json');

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

    public function testParseOptional()
    {
        /** @var ConfigInterface[] $configs */
        $configs = $this->parser->parse(__DIR__.self::FIXTURES_DIR.'/optional.json');

        $this->assertCount(2, $configs);

        $this->assertInstanceOf(ConfigInterface::class, $configs[1]);
        $this->assertSame('Contao\CoreBundle\ContaoCoreBundle', $configs[1]->getName());
        $this->assertSame(['core'], $configs[1]->getReplace());
        $this->assertTrue($configs[1]->loadInProduction());
        $this->assertTrue($configs[1]->loadInDevelopment());
        $this->assertSame(['Foo\BarBundle\FooBarBundle'], $configs[1]->getLoadAfter());
    }

    public function testParseNoBundle()
    {
        $this->setExpectedException('RuntimeException');

        $this->parser->parse(__DIR__.self::FIXTURES_DIR.'/no-bundle.json');
    }

    public function testParseMissingFile()
    {
        $this->setExpectedException('InvalidArgumentException');

        $this->parser->parse(__DIR__.self::FIXTURES_DIR.'/missing.json');
    }

    public function testParseInvalidJson()
    {
        $this->setExpectedException('RuntimeException');

        $this->parser->parse(__DIR__.self::FIXTURES_DIR.'/invalid.json');
    }

    public function testWillThrowExceptionIfFileNotExists()
    {
        $this->setExpectedException('InvalidArgumentException');

        $file = new SplFileInfo('iDoNotExist', 'relativePath', 'relativePathName');

        $this->parser->parse($file);
    }
}
