<?php
namespace Managur\Collection;

use ArrayObject;
use JsonSerializable;
use TypeError;
use ReflectionClass;

/**
 * Managur Generic Collection Class
 *
 * NOTE: Collections are NOT immutable. However, calling any of the functional methods (map/reduce/filter/sort etc) will
 * return a clone of the original with the required changes applied.
 *
 * @package Managur
 * @license MIT
 */
class Collection extends ArrayObject implements JsonSerializable
{

    const FILTER_USE_KEY = \ARRAY_FILTER_USE_KEY;
    const FILTER_USE_BOTH = \ARRAY_FILTER_USE_BOTH;

    /** @var string|null Enforce collection key type by defining type here */
    protected $keyType = null;

    /** @var string|null Enforce collection value type by defining type here */
    protected $valueType = null;


    public function __construct($items = [])
    {
        foreach ($this->arrayItems($items) as $key => $value) {
            $this->offsetSet($key, $value);
        }
    }

    /**
     * Prepare given items into array suitable for instantiation
     *
     * @param $items
     * @return array
     */
    private function arrayItems($items)
    {
        if (is_array($items)) {
            return $items;
        } elseif ($items instanceof self) {
            return $items->getArrayCopy();
        } elseif ($items instanceof JsonSerializable) {
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
     * @param $value
     * @return mixed
     */
    protected function keyStrategy($value)
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
    public function append($value)
    {
        $key = $this->keyStrategy($value);
        if ($key !== null) {
            $this->offsetSet($key, $value);
        } else {
            parent::append($this->checkType($value, $this->valueType));
        }
    }

    /**
     * @param mixed $index
     * @param mixed $value
     */
    public function offsetSet($index, $value)
    {
        $newIndex = $this->keyStrategy($value);

        if ($newIndex !== null) {
            $index = $newIndex;
        }

        parent::offsetSet(
            $this->checkType($index, $this->keyType),
            $this->checkType($value, $this->valueType)
        );
    }

    /**
     * Check Value Type
     *
     * If $type is not null, check that the provided value is the correct type. Throw a TypeError if not, and return the
     * value if it is.
     *
     * @param mixed $value
     * @param string|null $expectedType
     * @return mixed
     * @throws TypeError
     */
    private function checkType($value, ?string $expectedType)
    {
        if ($expectedType) {
            $valueType = \gettype($value);
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
     * @return self
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
     * @return Collection
     */
    public function mapInto(callable $callable, string $type): self
    {
        return self::newCollectionOfType($type, array_map($callable, $this->getArrayCopy()));
    }

    /**
     * Get a new collection of a given type
     *
     * @param string $type The collection type that you want an instance of
     * @param array $items The items that you want to collect immediately (defaults to nothing)
     * @return self
     */
    public static function newCollectionOfType(string $type, $items = []): Collection
    {
        if (!class_exists($type)) {
            throw new TypeError(sprintf('Unknown class name "%s"', $type));
        }
        if (!is_subclass_of($type, Collection::class) &&
            Collection::class !== $type
        ) {
            throw new TypeError(sprintf('Class "%s" is not a Collection type', $type));
        }
        return new $type($items);
    }

    /**
     * Map Function Against Collection and Return New Collection
     *
     * @param callable $callable May take up to two arguments: First is the array value, the second is the array key
     * @return static New collection of the same type
     */
    public function map(callable $callable): self
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
     * @param int $length   If length is given and is positive, then the sequence will have up to that many elements in it.
     *                      If the array is shorter than the length, then only the available array elements will be present.
     *                      If length is given and is negative then the sequence will stop that many elements from the end of the array.
     *                      If it is omitted, then the sequence will have everything from offset up until the end of the array.
     * @return static New collection of the same type
     */
    public function slice(int $offset, int $length = null): self
    {
        return $this->getNewInstance(array_slice($this->getArrayCopy(), $offset, $length));
    }

    /**
     * Walk Over Collection Entities
     *
     * Does not return; use map() for that
     *
     * @param callable $callable
     */
    public function each(callable $callable)
    {
        $array = $this->getArrayCopy();
        array_walk($array, $callable);
    }

    /**
     * Reduce Collection by Callable
     *
     * @param callable $callable Requires two arguments; the first to carry from the previous iteration, and the second as the item
     * @param mixed $carry Initial value, or returned if array is empty
     * @return mixed Type depends on return value of $callable
     */
    public function reduce(callable $callable, $carry = null)
    {
        $array = $this->getArrayCopy();
        return array_reduce($array, $callable, $carry);
    }

    /**
     * Filter Collection By Callable
     *
     * @param callable|null $callable Callback for each iteration. If null will just filter empty values from array
     * @param int|null $flag Collection::FILTER_USE_KEY or Collection::FILTER_USE_BOTH
     * @return static
     */
    public function filter($callable = null, int $flag = null): self
    {
        $array = $this->getArrayCopy();
        if ($callable && is_callable($callable)) {
            return $this->getNewInstance(array_filter($array, $callable, $flag));
        }
        return $this->getNewInstance(array_filter($array));
    }

    /**
     * Get First Entry From Collection
     *
     * @param callable|null $callable If provided will return the first value that this callback returns
     * @param mixed|null If no result is found, return this instead
     * @return mixed
     */
    public function first(callable $callable = null, $default = null)
    {
        if (!$callable) {
            $callable = function ($item) {
                return $item;
            };
        }
        $data = array_filter($this->getArrayCopy());
        foreach ($data as $key => $item) {
            if ($callable($item, $key)) {
                return $item;
            }
        }
        return $default;
    }

    /**
     * Get Last Entry From Collection
     *
     * @param callable|null $callable If provided will return the last value that this callback returns
     * @param mixed|null If no result is found, return this instead
     * @return mixed
     */
    public function last(callable $callable = null, $default = null)
    {
        if (!$callable) {
            $array = array_filter($this->getArrayCopy());
            return empty($array) ? $default : end($array);
        }
        return $this->map($callable)->last(null, $default);
    }

    /**
     * Check if Collection Contains Value
     *
     * @param mixed|callable $check
     * @return bool
     */
    public function contains($check): bool
    {
        if (is_callable($check)) {
            return (bool)$this->first($check);
        }
        return in_array($check, $this->getArrayCopy());
    }

    /**
     * Pop Entity Off Of The End Of The Collection
     *
     * @return mixed
     */
    public function pop()
    {
        $array  = $this->getArrayCopy();
        $popped = array_pop($array);
        $this->exchangeArray($array);
        return $popped;
    }

    /**
     * Push Entities On To The End Of The Collection
     *
     * @param array ...$vals
     */
    public function push(...$vals)
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
     * @param Collection $add
     * @return static
     */
    public function merge(Collection $add): self
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
     * @param callable|null $callable
     * @return static
     */
    public function sort(callable $callable = null): self
    {
        $data = $this->getArrayCopy();
        if ($callable) {
            if ($this->keyType) {
                uasort($data, $callable);
            } else {
                usort($data, $callable);
            }
        } else {
            if ($this->keyType) {
                asort($data);
            } else {
                sort($data);
            }
        }
        return $this->getNewInstance($data);
    }

    /**
     * Get a New Collection With Contents Sorted, Maintaining Index Associations
     *
     * @param callable|null $callable
     * @return static
     */
    public function asort(callable $callable = null): self
    {
        $data = $this->getArrayCopy();
        if ($callable) {
            uasort($data, $callable);
        } else {
            asort($data);
        }
        return $this->getNewInstance($data);
    }

    /**
     * Get a New Collection With Contents Shuffled
     *
     * @param $seed int|null
     * @return static
     */
    public function shuffle(int $seed = null): self
    {
        if ($seed !== null) {
            mt_srand($seed);
        }
        $data = $this->getArrayCopy();
        shuffle($data);
        return $this->getNewInstance($data);
    }

    /**
     * Join collection elements together with a string
     *
     * @param string $glue
     * @return static
     */
    public function implode($glue = ""): string
    {
        return implode($glue, $this->getArrayCopy());
    }

    /**
     * Get a New Instance of the Same Type
     *
     * @param $data
     * @return static
     */
    private function getNewInstance($data): self
    {
        $reflection = new ReflectionClass($this);
        if ($reflection->isAnonymous()) {
            return self::getTypedCollection($data, $this->keyType, $this->valueType);
        }
        return new static($data);
    }

    /**
     * Get a Strict Typed Collection
     *
     * Set the key and value types to enforce strict types within the collection
     *
     * @param mixed $data
     * @param string $keyType
     * @param string $valueType
     * @return self
     */
    private static function getTypedCollection($data, string $keyType = null, string $valueType = null): Collection
    {
        $ret = new class ($data, $keyType, $valueType) extends Collection {
            public function __construct($data, $keyType, $valueType)
            {
                $this->keyType = $keyType;
                $this->valueType = $valueType;
                parent::__construct($data);
            }
        };

        return $ret;
    }

    /**
     * Get a New Anonymnous Typed Value Collection
     *
     * @param string $valueType The type that all values must match
     * @param array $data
     * @return self
     */
    public static function newTypedValueCollection(string $valueType, $data = []): Collection
    {
        return self::getTypedCollection($data, null, $valueType);
    }

    /**
     * Get a New Anonymous Typed Key Collection
     *
     * @param string $keyType The type that all keys must match
     * @param array $data
     * @return self
     */
    public static function newTypedKeyCollection(string $keyType, $data = []): Collection
    {
        return self::getTypedCollection($data, $keyType);
    }

    /**
     * Get a New Anonymous Typed Collection
     *
     * @param string $keyType The type that all keys must match
     * @param string $valueType The type that all values must match
     * @param array $data
     * @return self
     */
    public static function newTypedCollection(?string $keyType, ?string $valueType, $data = []): Collection
    {
        return self::getTypedCollection($data, $keyType, $valueType);
    }

    /**
     * Get a JSON Serializable Representation of this Collection
     *
     * @return array|mixed
     */
    public function jsonSerialize()
    {
        return $this->getArrayCopy();
    }

    /**
     * @return bool
     */
    public function isEmpty(): bool
    {
        return $this->count() === 0;
    }

    /**
     * @return bool
     */
    public function isNotEmpty(): bool
    {
        return $this->count() > 0;
    }
}
