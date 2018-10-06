<?php
use Managur\Collection\Collection;

if (!function_exists('collect')) {
    function collect($items) : Collection
    {
        return new Collection($items);
    }
}

if (!function_exists('collectInto')) {
    function collectInto(string $collectionType, $items) : Collection
    {
        return new $collectionType($items);
    }
}
