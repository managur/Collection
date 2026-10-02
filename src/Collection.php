<?php

namespace Managur\Collection;

use ArrayAccess;
use ArrayIterator;
use Countable;
use Iterator;
use IteratorAggregate;
use JsonSerializable;
use Random\Engine\Mt19937;
use Random\Randomizer;
use TypeError;

use function gettype;

use const ARRAY_FILTER_USE_BOTH;
use const ARRAY_FILTER_USE_KEY;
use const SORT_REGULAR;

/**
 * Managur Generic Collection Class
 * NOTE: Collections are NOT immutable. However, calling any of the functional methods (map/reduce/filter/sort etc) will
 * return a clone of the original with the required changes applied.
 *
 * @package Managur
 * @license MIT
 *
 * @template TKey of array-key
 * @template TValue
 * @implements ArrayAccess<TKey, TValue>
 * @implements IteratorAggregate<TKey, TValue>
 */
class Collection implements ArrayAccess, Countable, IteratorAggregate, JsonSerializable
{
    public const FILTER_USE_KEY = ARRAY_FILTER_USE_KEY;
    public const FILTER_USE_BOTH = ARRAY_FILTER_USE_BOTH;

    /** @var string|null Enforce collection key type by defining type here */
    protected ?string $keyType = null;

    /** @var string|null Enforce collection value type by defining type here */
    protected ?string $valueType = null;

    /** @var array<array-key, TValue> Items held in the collection */
    private array $items = [];

    public function __construct(mixed $items = [])
    {
        $this->fill($this->arrayItems($items));
    }

    /**
     * Add Each Item Through offsetSet(), Applying The keyStrategy and Type Checks
     *
     * @param array<array-key, mixed> $items
     * @throws TypeError
     */
    private function fill(array $items): void
    {
        foreach ($items as $key => $value) {
            $this->offsetSet($key, $value);
        }
    }

    /**
     * Prepare given items into array suitable for instantiation
     *
     * @param mixed $items
     * @return array<TKey, TValue>
     */
    private function arrayItems(mixed $items): array
    {
        if (is_array($items)) {
            return $items;
        }

        if ($items instanceof self) {
            return $items->getArrayCopy();
        }

        if ($items instanceof JsonSerializable) {
            return $items->jsonSerialize();
        }

        return (array)$items;
    }

    /**
     * Collection Key Strategy
     *
     * Override this method in your own class to have your collection keys automatically set to your preference. For
     * example:
     * ```php
     * protected function keyStrategy($value)
     * {
     *     return $value->id();
     * }
     * ```
     *
     * @param mixed $value
     * @return mixed
     */
    protected function keyStrategy(mixed $value): mixed
    {
        return null;
    }

    /**
     * Append Value
     *
     * <strong>IMPORTANT:</strong> You cannot append if you are using typed keys unless you also implement an
     * appropriate keyStrategy method. If not, then you MUST specify an appropriate offset, either via offsetSet() or as
     * $collection[$offset] = $value;
     *
     * @param mixed $value
     */
    public function append(mixed $value): void
    {
        $this->offsetSet(null, $value);
    }

    /**
     * Check if Offset Exists
     *
     * Like isset(), a key holding a null value is treated as not existing
     *
     * @param mixed $key
     * @return bool
     */
    public function offsetExists(mixed $key): bool
    {
        return isset($this->items[$key]);
    }

    /**
     * Get Value At Offset
     *
     * @param mixed $key
     * @return TValue
     */
    public function offsetGet(mixed $key): mixed
    {
        return $this->items[$key];
    }

    /**
     * Set Value At Offset
     *
     * If the key is null (after applying the keyStrategy) the value is appended
     *
     * @param mixed $key
     * @param mixed $value
     */
    public function offsetSet(mixed $key, mixed $value): void
    {
        $newKey = $this->keyStrategy($value);

        if ($newKey !== null) {
            $key = $newKey;
        }

        $key = $this->checkType($key, $this->keyType);
        $value = $this->checkType($value, $this->valueType);

        if ($key === null) {
            $this->items[] = $value;
        } else {
            $this->items[$key] = $value;
        }
    }

    /**
     * Remove Value At Offset
     *
     * @param mixed $key
     */
    public function offsetUnset(mixed $key): void
    {
        unset($this->items[$key]);
    }

    /**
     * Count Items In The Collection
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->items);
    }

    /**
     * Get an Iterator Over The Collection
     *
     * @return Iterator<TKey, TValue>
     */
    public function getIterator(): Iterator
    {
        return new ArrayIterator($this->items);
    }

    /**
     * Get a Copy of The Items as an Array
     *
     * @return array<TKey, TValue>
     */
    public function getArrayCopy(): array
    {
        return $this->items;
    }

    /**
     * Replace The Contents of The Collection
     *
     * The new items are checked against the key and value types, and the keyStrategy is applied, exactly as they are on
     * construction. If any item is rejected, the collection is left unchanged.
     *
     * @param mixed $items
     * @return array<TKey, TValue> The previous contents of the collection
     * @throws TypeError
     */
    public function exchangeArray(mixed $items): array
    {
        $old = $this->items;
        $this->items = $this->getNewInstance($this->arrayItems($items))->items;
        return $old;
    }

    /**
     * Check Value Type
     *
     * If $type is not null, check that the provided value is the correct type. Throw a TypeError if not, and return the
     * value if it is.
     *
     * @template TChecked
     * @param TChecked $value
     * @param string|null $expectedType
     * @return TChecked
     * @throws TypeError
     */
    private function checkType(mixed $value, ?string $expectedType): mixed
    {
        if ($expectedType) {
            $valueType = gettype($value);
            if ($valueType === 'object') {
                if (!$value instanceof $expectedType) {
                    throw new TypeError(sprintf(
                        "Invalid object type. Should be %s: %s collected",
                        $expectedType,
                        get_class($value)
                    ));
                }
            } elseif ($valueType !== $expectedType) {
                throw new TypeError(sprintf(
                    "Invalid type. Should be %s: %s collected",
                    $expectedType,
                    $valueType
                ));
            }
        }
        return $value;
    }

    /**
     * Copy entries into a new collection
     *
     * @param string $type The collection type to copy into
     * @return Collection<array-key, mixed>
     * @throws TypeError
     */
    public function into(string $type): Collection
    {
        return self::newCollectionOfType($type, $this->getArrayCopy());
    }

    /**
     * Map collection into a new collection of a given type
     *
     * @param callable $callable
     * @param string $type
     * @return Collection<array-key, mixed>
     */
    public function mapInto(callable $callable, string $type): self
    {
        return self::newCollectionOfType($type, array_map($callable, $this->getArrayCopy()));
    }

    /**
     * Get a new collection of a given type
     *
     * @param string $type The collection type that you want an instance of
     * @param mixed $items The items that you want to collect immediately (defaults to nothing)
     * @return Collection<array-key, mixed>
     */
    public static function newCollectionOfType(string $type, mixed $items = []): Collection
    {
        if (class_exists($type) === false) {
            throw new TypeError(sprintf('Unknown class name "%s"', $type));
        }
        if (
            Collection::class !== $type &&
            is_subclass_of($type, Collection::class) === false
        ) {
            throw new TypeError(sprintf('Class "%s" is not a Collection type', $type));
        }
        return new $type($items);
    }

    /**
     * Map Function Against Collection and Return New Collection
     *
     * @param callable(TValue, TKey): mixed $callable May take up to two arguments: First is the array value, the second
     *                                                is the array key
     * @return static New collection of the same type
     */
    public function map(callable $callable): static
    {
        $array = $this->getArrayCopy();
        return $this->getNewInstance(array_map($callable, $array, array_keys($array)));
    }

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
    public function slice(int $offset, ?int $length = null): static
    {
        return $this->getNewInstance(array_slice($this->getArrayCopy(), $offset, $length));
    }

    /**
     * Walk Over Collection Entities
     *
     * Does not return; use map() for that
     *
     * @param callable(TValue, TKey): mixed $callable
     */
    public function each(callable $callable): void
    {
        $array = $this->getArrayCopy();
        array_walk($array, $callable);
    }

    /**
     * Reduce Collection by Callable
     *
     * @param callable(mixed, TValue): mixed $callable Requires two arguments; the first to carry from the previous
     *                                                 iteration, and the second as the item
     * @param mixed $carry Initial value, or returned if array is empty
     * @return mixed Type depends on return value of $callable
     */
    public function reduce(callable $callable, mixed $carry = null): mixed
    {
        $array = $this->getArrayCopy();
        return array_reduce($array, $callable, $carry);
    }

    /**
     * Filter Collection By Callable
     *
     * @param (callable(TValue, TKey=): mixed)|null $callable Callback for each iteration. If null will just filter
     *                                                       empty values from array
     * @param int $mode Collection::FILTER_USE_KEY or Collection::FILTER_USE_BOTH
     * @return static
     */
    public function filter(?callable $callable = null, int $mode = 0): static
    {
        $array = $this->getArrayCopy();
        if ($callable !== null) {
            return $this->getNewInstance(array_filter($array, $callable, $mode));
        }
        return $this->getNewInstance(array_filter($array));
    }

    /**
     * Get First Entry From Collection
     *
     * @template TDefault
     * @param (callable(TValue, array-key): mixed)|null $callable If provided will return the first value that this
     *   callback returns
     * @param TDefault $default If no result is found, return this instead
     * @return TValue|TDefault
     */
    public function first(?callable $callable = null, mixed $default = null): mixed
    {
        if ($callable === null) {
            $callable = static fn ($item, $key) => $item;
        }
        foreach ($this->items as $key => $item) {
            if ($callable($item, $key)) {
                return $item;
            }
        }
        return $default;
    }

    /**
     * Get Last Entry From Collection
     *
     * @template TDefault
     * @param (callable(TValue, TKey): mixed)|null $callable If provided will return the last value that this callback
     *   returns
     * @param TDefault $default If no result is found, return this instead
     * @return TValue|TDefault
     */
    public function last(?callable $callable = null, mixed $default = null): mixed
    {
        if ($callable === null) {
            $callable = static fn ($item, $key) => $item;
        }
        foreach (array_reverse($this->getArrayCopy(), true) as $key => $item) {
            if ($callable($item, $key)) {
                return $item;
            }
        }
        return $default;
    }

    /**
     * Check if Collection Contains Value
     *
     * Values are compared strictly. Use any() to search with a callback
     *
     * @param TValue $value
     * @return bool
     */
    public function contains(mixed $value): bool
    {
        return in_array($value, $this->items, true);
    }

    /**
     * Check if Any Entry Matches Callable
     *
     * @param callable(TValue, array-key): mixed $callable Takes the value and then the key. Return a truthy value to
     *   indicate a match
     * @return bool
     */
    public function any(callable $callable): bool
    {
        foreach ($this->items as $key => $item) {
            if ($callable($item, $key)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Pop Entity Off Of The End Of The Collection
     *
     * @return TValue|null
     */
    public function pop(): mixed
    {
        return array_pop($this->items);
    }

    /**
     * Push Entities On To The End Of The Collection
     *
     * @param mixed ...$vals
     */
    public function push(mixed ...$vals): void
    {
        foreach ($vals as $val) {
            $this->append($val);
        }
    }

    /**
     * Get a New Collection With Another Collection Merged In
     *
     * Returns a new object which contains the original and new elements
     *
     * @param Collection<TKey, TValue> $add
     * @return static
     */
    public function merge(Collection $add): static
    {
        $clone = clone($this);
        foreach ($add as $newElement) {
            $clone->append($newElement);
        }
        return $clone;
    }

    /**
     * Get a New Collection With Contents Sorted
     *
     * Functions the same as asort() if index types are constrained
     *
     * @param int $flags
     * @return static
     */
    public function sort(int $flags = SORT_REGULAR): static
    {
        $data = $this->getArrayCopy();
        if ($this->keyType) {
            asort($data, $flags);
        } else {
            sort($data, $flags);
        }
        return $this->getNewInstance($data);
    }

    /**
     * Get a New Collection With Contents Sorted By User Defined Callable
     *
     * Functions the same as uasort() if index types are constrained
     *
     * @param callable(TValue, TValue): int $callable
     * @return static
     */
    public function usort(callable $callable): static
    {
        $data = $this->getArrayCopy();
        if ($this->keyType) {
            uasort($data, $callable);
        } else {
            usort($data, $callable);
        }
        return $this->getNewInstance($data);
    }

    /**
     * Get a New Collection With Contents Sorted, Maintaining Index Associations
     *
     * @param int $flags
     * @return static
     */
    public function asort(int $flags = SORT_REGULAR): static
    {
        $data = $this->getArrayCopy();
        asort($data, $flags);
        return $this->getNewInstance($data);
    }

    /**
     * Get a New Collection With Contents Sorted, Maintaining Index Associations
     *
     * @param callable(TValue, TValue): int $callable
     * @return static
     */
    public function uasort(callable $callable): static
    {
        $data = $this->getArrayCopy();
        uasort($data, $callable);
        return $this->getNewInstance($data);
    }

    /**
     * Get a New Collection With Contents Shuffled
     *
     * @param int|null $seed
     * @return static
     */
    public function shuffle(?int $seed = null): static
    {
        $data = $this->getArrayCopy();
        if ($seed !== null) {
            $data = (new Randomizer(new Mt19937($seed)))->shuffleArray($data);
        } else {
            shuffle($data);
        }
        return $this->getNewInstance($data);
    }

    /**
     * Join collection elements together with a string
     *
     * @param string $glue
     * @param (callable(TValue, TKey): mixed)|null $callable
     * @return string
     */
    public function implode(string $glue = '', ?callable $callable = null): string
    {
        $array = $this->getArrayCopy();
        if ($callable) {
            $array = array_map($callable, $array, array_keys($array));
        }
        return implode($glue, $array);
    }

    /**
     * Get a New Instance of the Same Type
     *
     * @param array<TKey, TValue> $data
     * @return static
     */
    private function getNewInstance(array $data): static
    {
        $clone = clone($this);
        $clone->items = [];
        $clone->fill($data);
        return $clone;
    }

    /**
     * Get a Strict Typed Collection
     *
     * Set the key and value types to enforce strict types within the collection
     *
     * @param mixed $data
     * @param ?string $keyType
     * @param ?string $valueType
     * @return Collection<array-key, mixed>
     */
    private static function getTypedCollection(
        mixed $data,
        ?string $keyType = null,
        ?string $valueType = null,
    ): Collection {
        return new class ($data, $keyType, $valueType) extends Collection {
            public function __construct(mixed $data, ?string $keyType, ?string $valueType)
            {
                $this->keyType = $keyType;
                $this->valueType = $valueType;
                parent::__construct($data);
            }
        };
    }

    /**
     * Get a New Anonymous Typed Value Collection
     *
     * @param string $valueType The type that all values must match
     * @param mixed $data
     * @return Collection<array-key, mixed>
     */
    public static function newTypedValueCollection(string $valueType, mixed $data = []): Collection
    {
        return self::getTypedCollection($data, null, $valueType);
    }

    /**
     * Get a New Anonymous Typed Key Collection
     *
     * @param string $keyType The type that all keys must match
     * @param mixed $data
     * @return Collection<array-key, mixed>
     */
    public static function newTypedKeyCollection(string $keyType, mixed $data = []): Collection
    {
        return self::getTypedCollection($data, $keyType);
    }

    /**
     * Get a New Anonymous Typed Collection
     *
     * @param ?string $keyType The type that all keys must match
     * @param ?string $valueType The type that all values must match
     * @param mixed $data
     * @return Collection<array-key, mixed>
     */
    public static function newTypedCollection(?string $keyType, ?string $valueType, mixed $data = []): Collection
    {
        return self::getTypedCollection($data, $keyType, $valueType);
    }

    /**
     * Get a JSON Serializable Representation of this Collection
     *
     * @return array<TKey, TValue>
     */
    public function jsonSerialize(): array
    {
        return $this->getArrayCopy();
    }

    public function isEmpty(): bool
    {
        return $this->count() === 0;
    }

    public function isNotEmpty(): bool
    {
        return $this->count() > 0;
    }
}
