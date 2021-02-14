<?php

use Managur\Collection\ArrayCollection;

if (!function_exists('collect')) {
    function collect(mixed $items): \Managur\Collection\Collection
    {
        return new ArrayCollection($items);
    }
}

if (!function_exists('collectInto')) {
    function collectInto(string $collectionType, mixed $items): \Managur\Collection\Collection
    {
        return new $collectionType($items);
    }
}
