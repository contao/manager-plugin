<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerPlugin\HttpCache;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * By default, Contao ships with a reverse proxy based on Symfony's HttpCache
 * and the EventDispatchingHttpCache implementation of the FOSHttpCache library.
 * You can use this interface to provide your own FOSHttpCache event subscribers.
 */
interface FOSHttpCacheSubscriberPluginInterface
{
    /**
     * Gets an array of subscribers.
     *
     * @return EventSubscriberInterface[]
     */
    public function getSubscribers();
}
