<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2018 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\ManagerPlugin\Tests\Bundle\Parser;

use Contao\ManagerPlugin\Bundle\Config\ConfigInterface;
use Contao\ManagerPlugin\Bundle\Parser\IniParser;
use PHPUnit\Framework\TestCase;

class IniParserTest extends TestCase
{
    /**
     * @var IniParser
     */
    private $parser;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->parser = new IniParser(__DIR__.'/../../Fixtures/Bundle/IniParser');
    }

    public function testCanBeInstantiated()
    {
        $this->assertInstanceOf('Contao\ManagerPlugin\Bundle\Parser\IniParser', $this->parser);
        $this->assertInstanceOf('Contao\ManagerPlugin\Bundle\Parser\ParserInterface', $this->parser);
    }

    public function testSupportsIniFiles()
    {
        $this->assertTrue($this->parser->supports('foobar', 'ini'));
        $this->assertTrue($this->parser->supports('with-requires'));
        $this->assertFalse($this->parser->supports('foobar'));
    }

    public function testParsesRequires()
    {
        /** @var ConfigInterface[] $configs */
        $configs = $this->parser->parse('with-requires');

        $this->assertCount(4, $configs);
        $this->assertInstanceOf(ConfigInterface::class, $configs[0]);

        $this->assertSame('with-requires', $configs[0]->getName());
        $this->assertSame([], $configs[0]->getReplace());
        $this->assertSame(['core', 'news', 'without-ini', 'calendar'], $configs[0]->getLoadAfter());
        $this->assertTrue($configs[0]->loadInProduction());
        $this->assertTrue($configs[0]->loadInDevelopment());
    }

    public function testParsesRecursiveRequires()
    {
        /** @var ConfigInterface[] $configs */
        $configs = $this->parser->parse('recursion1');

        $this->assertCount(2, $configs);
        $this->assertInstanceOf(ConfigInterface::class, $configs[0]);
        $this->assertInstanceOf(ConfigInterface::class, $configs[1]);

        $this->assertSame(['recursion2'], $configs[0]->getLoadAfter());
        $this->assertSame(['recursion1'], $configs[1]->getLoadAfter());
    }

    public function testParsesDirectoriesWithoutIniFile()
    {
        /** @var ConfigInterface[] $configs */
        $configs = $this->parser->parse('without-ini');

        $this->assertCount(1, $configs);
        $this->assertInstanceOf(ConfigInterface::class, $configs[0]);

        $this->assertSame('without-ini', $configs[0]->getName());
        $this->assertSame([], $configs[0]->getReplace());
        $this->assertNotEmpty($configs[0]->getLoadAfter());
        $this->assertTrue($configs[0]->loadInProduction());
        $this->assertTrue($configs[0]->loadInDevelopment());
    }

    public function testParsesIniFilesWithoutRequires()
    {
        /** @var ConfigInterface[] $configs */
        $configs = $this->parser->parse('without-requires');

        $this->assertCount(1, $configs);
        $this->assertInstanceOf(ConfigInterface::class, $configs[0]);

        $this->assertSame('without-requires', $configs[0]->getName());
        $this->assertSame([], $configs[0]->getReplace());
        $this->assertNotEmpty($configs[0]->getLoadAfter());
        $this->assertTrue($configs[0]->loadInProduction());
        $this->assertTrue($configs[0]->loadInDevelopment());
    }

    public function testParsesNonExistingDirectories()
    {
        /** @var ConfigInterface[] $configs */
        $configs = $this->parser->parse('foobar');

        $this->assertCount(1, $configs);
        $this->assertInstanceOf(ConfigInterface::class, $configs[0]);

        $this->assertSame('foobar', $configs[0]->getName());
        $this->assertSame([], $configs[0]->getReplace());
        $this->assertNotEmpty($configs[0]->getLoadAfter());
        $this->assertTrue($configs[0]->loadInProduction());
        $this->assertTrue($configs[0]->loadInDevelopment());
    }

    /**
     * @runInSeparateProcess
     */
    public function testFailsParsingABrokenIniFile()
    {
        // http://stackoverflow.com/questions/1225776/test-the-return-value-of-a-method-that-triggers-an-error-with-phpunit
        \PHPUnit_Framework_Error_Warning::$enabled = false;
        \PHPUnit_Framework_Error_Notice::$enabled = false;
        error_reporting(0);

        $this->expectException('RuntimeException');

        $this->parser->parse('broken-ini');
    }
}
