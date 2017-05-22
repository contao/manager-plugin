<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\ManagerPlugin\Tests\Bundle\Parser;

use Contao\ManagerPlugin\Bundle\Parser\DelegatingParser;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;

class DelegatingParserTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DelegatingParser
     */
    private $parser;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->parser = new DelegatingParser();
    }

    public function testInstantiation()
    {
        $this->assertInstanceOf('Contao\ManagerPlugin\Bundle\Parser\DelegatingParser', $this->parser);
        $this->assertInstanceOf('Contao\ManagerPlugin\Bundle\Parser\ParserInterface', $this->parser);
    }

    public function testSupportsWithoutParsers()
    {
        $this->assertFalse($this->parser->supports('foobar'));
    }

    public function testParseWithoutParsers()
    {
        $this->setExpectedException('InvalidArgumentException');

        $this->assertFalse($this->parser->parse('foobar'));
    }

    public function testSupportsWithSupportedParser()
    {
        $parser = $this->getMock(ParserInterface::class);
        $parser->expects($this->once())->method('supports')->willReturn(true);
        $parser->expects($this->never())->method('parse');

        $this->parser->addParser($parser);

        $this->assertTrue($this->parser->supports('foobar'));
    }

    public function testSupportsWithUnsupportedParser()
    {
        $parser = $this->getMock(ParserInterface::class);
        $parser->expects($this->once())->method('supports')->willReturn(false);
        $parser->expects($this->never())->method('parse');

        $this->parser->addParser($parser);

        $this->assertFalse($this->parser->supports('foobar'));
    }

    public function testParseWithSupportedParser()
    {
        $parser = $this->getMock(ParserInterface::class);
        $parser->expects($this->once())->method('supports')->willReturn(true);
        $parser->expects($this->once())->method('parse')->willReturn([]);

        $this->parser->addParser($parser);

        $this->assertSame([], $this->parser->parse('foobar'));
    }

    public function testParseWithUnsupportedParser()
    {
        $this->setExpectedException('InvalidArgumentException');

        $parser = $this->getMock(ParserInterface::class);
        $parser->expects($this->once())->method('supports')->willReturn(false);
        $parser->expects($this->never())->method('parse');

        $this->parser->addParser($parser);

        $this->parser->parse('foobar');
    }
}
