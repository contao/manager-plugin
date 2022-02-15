<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerPlugin\Dependency;

trait DependencyResolverTrait
{
    /**
     * Returns a list of array keys ordered by their dependencies.
     *
     * @throws UnresolvableDependenciesException
     *
     * @return array<string>
     */
    protected function orderByDependencies(array $dependencies): array
    {
        $ordered = [];
        $available = array_keys($dependencies);

        while (0 !== \count($dependencies)) {
            $success = $this->doResolve($dependencies, $ordered, $available);

            if (false === $success) {
                throw new UnresolvableDependenciesException("The dependencies order could not be resolved.\n".print_r($dependencies, true));
            }
        }

        return $ordered;
    }

    /**
     * Resolves the dependency order.
     */
    private function doResolve(array &$dependencies, array &$ordered, array $available): bool
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
     */
    private function canBeResolved(array $requires, array $available, array $ordered): bool
    {
        if (0 === \count($requires)) {
            return true;
        }

        return 0 === \count(array_diff(array_intersect($requires, $available), $ordered));
    }
}
