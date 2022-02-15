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

class DelegatingParser implements ParserInterface
{
    /**
     * @var array<ParserInterface>
     */
    private $parsers = [];

    public function addParser(ParserInterface $parser): void
    {
        $this->parsers[] = $parser;
    }

    /**
     * {@inheritdoc}
     */
    public function parse($resource, $type = null): array
    {
        foreach ($this->parsers as $parser) {
            if ($parser->supports($resource, $type)) {
                return $parser->parse($resource, $type);
            }
        }

        throw new \InvalidArgumentException(sprintf('Cannot parse resources "%s" (type: %s)', $resource, $type));
    }

    /**
     * {@inheritdoc}
     */
    public function supports($resource, $type = null): bool
    {
        foreach ($this->parsers as $parser) {
            if ($parser->supports($resource, $type)) {
                return true;
            }
        }

        return false;
    }
}
