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
    protected function setUp(): void
    {
        parent::setUp();

        $this->parser = new IniParser(__DIR__.'/../../Fixtures/Bundle/IniParser');
    }

    public function testCanBeInstantiated(): void
    {
        $this->assertInstanceOf('Contao\ManagerPlugin\Bundle\Parser\IniParser', $this->parser);
        $this->assertInstanceOf('Contao\ManagerPlugin\Bundle\Parser\ParserInterface', $this->parser);
    }

    public function testSupportsIniFiles(): void
    {
        $this->assertTrue($this->parser->supports('foobar', 'ini'));
        $this->assertTrue($this->parser->supports('with-requires'));
        $this->assertFalse($this->parser->supports('foobar'));
    }

    public function testParsesRequires(): void
    {
        /** @var array<ConfigInterface> $configs */
        $configs = $this->parser->parse('with-requires');

        $this->assertCount(4, $configs);
        $this->assertInstanceOf(ConfigInterface::class, $configs[0]);

        $this->assertSame('with-requires', $configs[0]->getName());
        $this->assertSame([], $configs[0]->getReplace());
        $this->assertSame(['calendar', 'core', 'news', 'without-ini'], $configs[0]->getLoadAfter());
        $this->assertTrue($configs[0]->loadInProduction());
        $this->assertTrue($configs[0]->loadInDevelopment());
    }

    public function testParsesRecursiveRequires(): void
    {
        /** @var array<ConfigInterface> $configs */
        $configs = $this->parser->parse('recursion1');

        $this->assertCount(2, $configs);
        $this->assertInstanceOf(ConfigInterface::class, $configs[0]);
        $this->assertInstanceOf(ConfigInterface::class, $configs[1]);

        $this->assertSame(['recursion2'], $configs[0]->getLoadAfter());
        $this->assertSame(['recursion1'], $configs[1]->getLoadAfter());
    }

    public function testParsesRecursiveRequiresOnlyOnce(): void
    {
        /** @var array<ConfigInterface> $configs */
        $configs = $this->parser->parse('recursion1');
        $this->assertSame([], $this->parser->parse('recursion2'));

        $this->assertCount(2, $configs);
        $this->assertInstanceOf(ConfigInterface::class, $configs[0]);
        $this->assertInstanceOf(ConfigInterface::class, $configs[1]);

        $this->assertSame(['recursion2'], $configs[0]->getLoadAfter());
        $this->assertSame(['recursion1'], $configs[1]->getLoadAfter());
    }

    public function testParsesDirectoriesWithoutIniFile(): void
    {
        /** @var array<ConfigInterface> $configs */
        $configs = $this->parser->parse('without-ini');

        $this->assertCount(1, $configs);
        $this->assertInstanceOf(ConfigInterface::class, $configs[0]);

        $this->assertSame('without-ini', $configs[0]->getName());
        $this->assertSame([], $configs[0]->getReplace());
        $this->assertNotEmpty($configs[0]->getLoadAfter());
        $this->assertTrue($configs[0]->loadInProduction());
        $this->assertTrue($configs[0]->loadInDevelopment());
    }

    public function testParsesIniFilesWithoutRequires(): void
    {
        /** @var array<ConfigInterface> $configs */
        $configs = $this->parser->parse('without-requires');

        $this->assertCount(1, $configs);
        $this->assertInstanceOf(ConfigInterface::class, $configs[0]);

        $this->assertSame('without-requires', $configs[0]->getName());
        $this->assertSame([], $configs[0]->getReplace());
        $this->assertNotEmpty($configs[0]->getLoadAfter());
        $this->assertTrue($configs[0]->loadInProduction());
        $this->assertTrue($configs[0]->loadInDevelopment());
    }

    public function testParsesNonExistingDirectories(): void
    {
        /** @var array<ConfigInterface> $configs */
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
    public function testFailsParsingABrokenIniFile(): void
    {
        $class = new \ReflectionClass(\PHPUnit\Framework\Error\Warning::class);

        // http://stackoverflow.com/questions/1225776/test-the-return-value-of-a-method-that-triggers-an-error-with-phpunit
        if ($class->hasProperty('enabled')) {
            \PHPUnit\Framework\Error\Warning::$enabled = false;
            \PHPUnit\Framework\Error\Notice::$enabled = false;
        }

        error_reporting(0);

        $this->expectException('RuntimeException');

        $this->parser->parse('broken-ini');
    }
}
