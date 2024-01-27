<?php

use Managur\Collection\Collection;

/**
 * @codeCoverageIgnore
 */
if (!function_exists('collect')) {
    function collect(mixed $items): Collection
    {
        return new Collection($items);
    }
}

/**
 * @codeCoverageIgnore
 */
if (!function_exists('collectInto')) {
    function collectInto(string $collectionType, mixed $items): Collection
    {
        return new $collectionType($items);
    }
}
