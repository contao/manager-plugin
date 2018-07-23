<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerPlugin\Routing;

use Symfony\Component\Config\Loader\LoaderResolverInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\RouteCollection;

interface RoutingPluginInterface
{
    /**
     * Returns a collection of routes for this bundle.
     *
     * @return RouteCollection|null
     */
    public function getRouteCollection(LoaderResolverInterface $resolver, KernelInterface $kernel);
}
