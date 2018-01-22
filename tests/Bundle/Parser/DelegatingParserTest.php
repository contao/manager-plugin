<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2018 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\ManagerPlugin\Tests\Bundle\Parser;

use Contao\ManagerPlugin\Bundle\Parser\DelegatingParser;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use PHPUnit\Framework\TestCase;

class DelegatingParserTest extends TestCase
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

    public function testCanBeInstantiated()
    {
        $this->assertInstanceOf('Contao\ManagerPlugin\Bundle\Parser\DelegatingParser', $this->parser);
        $this->assertInstanceOf('Contao\ManagerPlugin\Bundle\Parser\ParserInterface', $this->parser);
    }

    public function testDelegatesTheSupportsCalls()
    {
        $parser = $this->createMock(ParserInterface::class);

        $parser
            ->method('supports')
            ->willReturn(true)
        ;

        $this->parser->addParser($parser);

        $this->assertTrue($this->parser->supports('foobar'));
    }

    public function testDelegatesTheParseCalls()
    {
        $parser = $this->createMock(ParserInterface::class);

        $parser
            ->method('supports')
            ->willReturn(true)
        ;

        $parser
            ->method('parse')
            ->willReturn([])
        ;

        $this->parser->addParser($parser);

        $this->assertSame([], $this->parser->parse('foobar'));
    }

    public function testDoesNotSupportAnythingIfThereAreNoParsers()
    {
        $this->assertFalse($this->parser->supports('foobar'));
    }

    public function testFailsToParseAResourceIfThereAreNoParsers()
    {
        $this->expectException('InvalidArgumentException');

        $this->parser->parse('foobar');
    }

    public function testDoesNotSupportAnythingIfThereIsNoMatchingParser()
    {
        $parser = $this->createMock(ParserInterface::class);

        $parser
            ->method('supports')
            ->willReturn(false)
        ;

        $this->parser->addParser($parser);

        $this->assertFalse($this->parser->supports('foobar'));
    }

    public function testFailsToParseAResourceIfThereIsNoMatchingParser()
    {
        $parser = $this->createMock(ParserInterface::class);

        $parser
            ->method('supports')
            ->willReturn(false)
        ;

        $parser
            ->expects($this->never())
            ->method('parse')
        ;

        $this->parser->addParser($parser);

        $this->expectException('InvalidArgumentException');

        $this->parser->parse('foobar');
    }
}
