<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerPlugin\HttpKernel;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

interface HttpCacheSubscriberPluginInterface
{
    /**
     * Returns an array of event subscribers for the HTTP cache.
     *
     * @return array<EventSubscriberInterface>
     */
    public function getHttpCacheSubscribers(): array;
}
