<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\ManagerPlugin\Dependency;

trait DependencyResolverTrait
{
    /**
     * Returns a list of array keys ordered by their dependencies.
     *
     * @param array $dependencies
     *
     * @throws UnresolvableDependenciesException
     *
     * @return array
     */
    protected function orderByDependencies(array $dependencies)
    {
        $ordered = [];
        $available = array_keys($dependencies);

        while (0 !== \count($dependencies)) {
            $success = $this->doResolve($dependencies, $ordered, $available);

            if (false === $success) {
                throw new UnresolvableDependenciesException(
                    "The dependencies order could not be resolved.\n".print_r($dependencies, true)
                );
            }
        }

        return $ordered;
    }

    /**
     * Resolves the dependency order.
     *
     * @param array $dependencies
     * @param array $ordered
     * @param array $available
     *
     * @return bool
     */
    private function doResolve(array &$dependencies, array &$ordered, array $available)
    {
        $failed = true;

        foreach ($dependencies as $name => $requires) {
            if (true === $this->canBeResolved($requires, $available, $ordered)) {
                $failed = false;
                $ordered[] = $name;

                unset($dependencies[$name]);
            }
        }

        return !$failed;
    }

    /**
     * Checks whether the requirements can be resolved.
     *
     * @param array $requires
     * @param array $available
     * @param array $ordered
     *
     * @return bool
     */
    private function canBeResolved(array $requires, array $available, array $ordered)
    {
        if (0 === \count($requires)) {
            return true;
        }

        return 0 === \count(array_diff(array_intersect($requires, $available), $ordered));
    }
}
