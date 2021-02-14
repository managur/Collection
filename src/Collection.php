<?php

namespace Managur\Collection;

use ArrayAccess;
use Countable;
use JsonSerializable;
use TypeError;

/**
 * Managur Collection Interfaces
 *
 * @package Managur
 * @license MIT
 */
interface Collection extends JsonSerializable, ArrayAccess, Countable
{
    public const FILTER_USE_KEY = ARRAY_FILTER_USE_KEY;
    public const FILTER_USE_BOTH = ARRAY_FILTER_USE_BOTH;

    /**
     * Append Value
     *
     * <strong>IMPORTANT:</strong> You cannot append if you are using typed keys unless you also implement an
     * appropriate keyStrategy method. If not, then you MUST specify an appropriate offset, either via offsetSet() or as
     * $collection[$offset] = $value;
     *
     * @param mixed $value
     */
    public function append(mixed $value): void;

    /**
     * @param mixed $key
     * @param mixed $value
     */
    public function offsetSet(mixed $key, mixed $value): void;

    /**
     * Copy entries into a new collection
     *
     * @param string $type The collection type to copy into
     * @return Collection
     * @throws TypeError
     */
    public function into(string $type): Collection;

    /**
     * Map collection into a new collection of a given type
     *
     * @param callable $callable
     * @param string $type
     * @return Collection
     */
    public function mapInto(callable $callable, string $type): Collection;

    /**
     * Get a new collection of a given type
     *
     * @param string $type The collection type that you want an instance of
     * @param array $items The items that you want to collect immediately (defaults to nothing)
     * @return Collection
     */
    public static function newCollectionOfType(string $type, $items = []): Collection;

    /**
     * Map Function Against ArrayCollection and Return New ArrayCollection
     *
     * @param callable $callable May take up to two arguments: First is the array value, the second is the array key
     * @return static New collection of the same type
     */
    // phpcs:ignore Squiz.WhiteSpace.ScopeKeywordSpacing.Incorrect -- Broken until 3.6.0
    public function map(callable $callable): static;

    /**
     * Slice the sequence of elements from the array as per the `$offset` and `$length`
     *
     * @see https://www.php.net/manual/en/function.array-slice.php
     *
     * @param int $offset   If offset is non-negative, the sequence will start at that offset in the array.
     *                      If offset is negative, the sequence will start that far from the end of the array.
     *                      The offset parameter denotes the position in the array, not the key.
     * @param ?int $length  If length is given and is positive, then the sequence will have up to that many elements in
     *                      it.
     *                      If the array is shorter than the length, then only the available array elements will be
     *                      present.
     *                      If length is given and is negative then the sequence will stop that many elements from the
     *                      end of the array.
     *                      If it is omitted, then the sequence will have everything from offset up until the end of the
     *                      array.
     * @return static New collection of the same type
     */
    // phpcs:ignore Squiz.WhiteSpace.ScopeKeywordSpacing.Incorrect -- Broken until 3.6.0
    public function slice(int $offset, int $length = null): static;

    /**
     * Walk Over Collection Entities
     *
     * Does not return; use map() for that
     *
     * @param callable $callable
     */
    public function each(callable $callable): void;

    /**
     * Reduce Collection by Callable
     *
     * @param callable $callable Requires two arguments; the first to carry from the previous iteration, and the second
     *                           as the item
     * @param mixed $carry Initial value, or returned if array is empty
     * @return mixed Type depends on return value of $callable
     */
    public function reduce(callable $callable, $carry = null): mixed;

    /**
     * Filter Collection By Callable
     *
     * @param callable|null $callable Callback for each iteration. If null will just filter empty values from array
     * @param int|null $flag Collection::FILTER_USE_KEY or Collection::FILTER_USE_BOTH
     * @return static
     */
    // phpcs:ignore Squiz.WhiteSpace.ScopeKeywordSpacing.Incorrect -- Broken until 3.6.0
    public function filter($callable = null, int $flag = null): static;

    /**
     * Get First Entry From Collection
     *
     * @param ?callable(mixed $item, mixed $key):mixed $callable If provided will return the first value that this
     *   callback returns
     * @param mixed If no result is found, return this instead
     * @return mixed
     */
    public function first(?callable $callable = null, mixed $default = null): mixed;

    /**
     * Get Last Entry From Collection
     *
     * @param callable|null $callable If provided will return the last value that this callback returns
     * @param mixed|null If no result is found, return this instead
     * @return mixed
     */
    public function last(callable $callable = null, $default = null): mixed;

    /**
     * Check if Collection Contains Value
     *
     * @param mixed|callable $check
     * @return bool
     */
    public function contains(mixed $check): bool;

    /**
     * Pop Entity Off Of The End Of The Collection
     *
     * @return mixed
     */
    public function pop(): mixed;

    /**
     * Push Entities On To The End Of The Collection
     *
     * @param array ...$vals
     */
    public function push(...$vals): void;

    /**
     * Get a New ArrayCollection With Another Collection Merged In
     *
     * Returns a new object which contains the original and new elements
     *
     * @param Collection $add
     * @return static
     */
    // phpcs:ignore Squiz.WhiteSpace.ScopeKeywordSpacing.Incorrect -- Broken until 3.6.0
    public function merge(Collection $add): static;

    /**
     * Get a New Collection With Contents Sorted
     *
     * Functions the same as asort() if index types are constrained
     *
     * @param int $flags
     * @return static
     */
    // phpcs:ignore Squiz.WhiteSpace.ScopeKeywordSpacing.Incorrect -- Broken until 3.6.0
    public function sort(int $flags = SORT_REGULAR): static;

    /**
     * Get a New Collection With Contents Sorted By User Defined Callable
     *
     * Functions the same as uasort() if index types are constrained
     *
     * @param $callable
     * @return static
     */
    // phpcs:ignore Squiz.WhiteSpace.ScopeKeywordSpacing.Incorrect -- Broken until 3.6.0
    public function usort(callable $callable): static;

    /**
     * Get a New Collection With Contents Sorted, Maintaining Index Associations
     *
     * @param int $flags
     * @return static
     */
    // phpcs:ignore Squiz.WhiteSpace.ScopeKeywordSpacing.Incorrect -- Broken until 3.6.0
    public function asort(int $flags = SORT_REGULAR): static;

    /**
     * Get a New Collection With Contents Sorted, Maintaining Index Associations
     *
     * @param callable $callable
     * @return static
     */
    // phpcs:ignore Squiz.WhiteSpace.ScopeKeywordSpacing.Incorrect -- Broken until 3.6.0
    public function uasort(callable $callable): static;

    /**
     * Get a New Collection With Contents Shuffled
     *
     * @param $seed int|null
     * @return static
     */
    // phpcs:ignore Squiz.WhiteSpace.ScopeKeywordSpacing.Incorrect -- Broken until 3.6.0
    public function shuffle(int $seed = null): static;

    /**
     * Join collection elements together with a string
     *
     * @param string $glue
     * @param callable|null $callable
     * @return string
     */
    public function implode($glue = '', callable $callable = null): string;

    /**
     * Get a New Anonymous Typed Key Collection
     *
     * @param string $keyType The type that all keys must match
     * @param mixed $data
     * @return Collection
     */
    public static function newTypedKeyCollection(string $keyType, mixed $data = []): Collection;

    /**
     * Get a New Anonymous Typed Collection
     *
     * @param ?string $keyType The type that all keys must match
     * @param ?string $valueType The type that all values must match
     * @param mixed $data
     * @return Collection
     */
    public static function newTypedCollection(?string $keyType, ?string $valueType, mixed $data = []): Collection;

    public function isEmpty(): bool;

    public function isNotEmpty(): bool;
}
