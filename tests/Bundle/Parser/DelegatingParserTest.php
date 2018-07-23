<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
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
    protected function setUp(): void
    {
        parent::setUp();

        $this->parser = new DelegatingParser();
    }

    public function testCanBeInstantiated(): void
    {
        $this->assertInstanceOf('Contao\ManagerPlugin\Bundle\Parser\DelegatingParser', $this->parser);
        $this->assertInstanceOf('Contao\ManagerPlugin\Bundle\Parser\ParserInterface', $this->parser);
    }

    public function testDelegatesTheSupportsCalls(): void
    {
        $parser = $this->createMock(ParserInterface::class);
        $parser
            ->method('supports')
            ->willReturn(true)
        ;

        $this->parser->addParser($parser);

        $this->assertTrue($this->parser->supports('foobar'));
    }

    public function testDelegatesTheParseCalls(): void
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

    public function testDoesNotSupportAnythingIfThereAreNoParsers(): void
    {
        $this->assertFalse($this->parser->supports('foobar'));
    }

    public function testFailsToParseAResourceIfThereAreNoParsers(): void
    {
        $this->expectException('InvalidArgumentException');

        $this->parser->parse('foobar');
    }

    public function testDoesNotSupportAnythingIfThereIsNoMatchingParser(): void
    {
        $parser = $this->createMock(ParserInterface::class);
        $parser
            ->method('supports')
            ->willReturn(false)
        ;

        $this->parser->addParser($parser);

        $this->assertFalse($this->parser->supports('foobar'));
    }

    public function testFailsToParseAResourceIfThereIsNoMatchingParser(): void
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
