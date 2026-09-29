<?php

/**
 * Managur Collection Helper Functions
 *
 * Allows you to collect(['items']) from a convenient function instead of instantiating a new object manually.
 * Also allows collecting directly into a specific collection type
 */

use Managur\Collection\Collection;

if (!function_exists('collect')) { // @codeCoverageIgnore
    /**
     * @return Collection<array-key, mixed>
     */
    function collect(mixed $items): Collection
    {
        return new Collection($items);
    }
}

if (!function_exists('collectInto')) { // @codeCoverageIgnore
    /**
     * @return Collection<array-key, mixed>
     */
    function collectInto(string $collectionType, mixed $items): Collection
    {
        return Collection::newCollectionOfType($collectionType, $items);
    }
}
