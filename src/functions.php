<?php

use Managur\Collection\ArrayCollection;
use Managur\Collection\Collection;

if (!function_exists('collect')) {
    function collect(mixed $items): Collection
    {
        return new ArrayCollection($items);
    }
}

if (!function_exists('collectInto')) {
    function collectInto(string $collectionType, mixed $items): Collection
    {
        return new $collectionType($items);
    }
}
