<?php

/**
 * Managur Collection Helper Functions
 *
 * Allows you to collect(['items']) from a convenient function instead of instantiating a new object manually.
 * Also allows collecting directly into a specific collection type
 */

use Managur\Collection\Collection;

if (!function_exists('collect')) { // @codeCoverageIgnore
    function collect(mixed $items): Collection
    {
        return new Collection($items);
    }
}

if (!function_exists('collectInto')) { // @codeCoverageIgnore
    /**
     * Collect Items Into A Specific Collection Type
     *
     * @param class-string<Collection> $collectionType
     * @param mixed $items
     * @return Collection
     */
    function collectInto(string $collectionType, mixed $items): Collection
    {
        return new $collectionType($items);
    }
}
