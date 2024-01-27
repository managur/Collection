<?php declare(strict_types=1);

namespace tests;

use Managur\Collection\Collection;
use PHPUnit\Framework\TestCase;

final class CollectionTest extends TestCase
{
    /**
     * @test
     */
    public function nonIterableTypeCollects(): void
    {
        $str = 'a string of text';
        $collection = new Collection($str);
        $this->assertInstanceOf(Collection::class, $collection);
        $this->assertEquals($str, $collection[0]);
    }

    /**
     * @dataProvider collectibles
     * @param mixed $data
     * @test
     */
    public function basicTypeAndCount($data): void
    {
        $collection = new Collection($data);
        $this->assertInstanceOf(Collection::class, $collection);
        $this->assertEquals(count($data), count($collection));

    }

    /**
     * @dataProvider collectibles
     * @param mixed $data
     * @test
     */
    public function appendByMethod($data): void
    {
        $collection = new Collection($data);
        $collection->append('test');
        $this->assertEquals(count($data)+1, count($collection));
    }

    /**
     * @dataProvider collectibles
     * @param mixed $data
     * @test
     */
    public function appendLikeArray($data): void
    {
        $collection = new Collection($data);
        $collection[] = 'test';
        $this->assertEquals(count($data)+1, count($collection));
    }

    /**
     * @dataProvider iterables
     * @param mixed $data
     * @test
     */
    public function offsetSetByMethod($data): void
    {
        $collection = new Collection;
        foreach ($data as $key=>$val) {
            $collection->offsetSet($key, $val);
            $this->assertEquals($val, $collection[$key]);
        }
        $this->assertEquals(count($data), count($collection));
    }

    /**
     * @dataProvider iterables
     * @param mixed $data
     * @test
     */
    public function offsetSetLikeArray($data): void
    {
        $collection = new Collection;
        foreach ($data as $key=>$val) {
            $collection[$key] = $val;
            $this->assertEquals($val, $collection[$key]);
        }
        $this->assertEquals(count($data), count($collection));
    }

    /**
     * @dataProvider iterables
     * @param $data
     * @param null $keyType
     * @param null $valueType
     * @test
     */
    public function typedCollections($data, $keyType=null, $valueType=null): void
    {
        $collection = $this->getTypedCollection($data, $keyType, $valueType);
        foreach ($data as $key=>$val) {
            $this->assertEquals($val, $collection[$key]);
        }
        $this->assertEquals(count($data), count($collection));
    }

    /**
     * @dataProvider mismatchedTypedCollections
     * @param $data
     * @param $keyType
     * @param $valueType
     * @test
     */
    public function typedCollectionsWithIncorrectTypes($data, $keyType, $valueType): void
    {
        $this->expectException(\TypeError::class);
        $this->getTypedCollection($data,$keyType, $valueType);
    }

    /**
     * @dataProvider iterables
     * @param $data
     * @test
     */
    public function checkMapOfCollectionData($data): void
    {
        $collection = new Collection($data);
        $collection->map(function ($value, $key) use ($data) {
            $this->assertEquals($data[$key], $value);
            return $value;
        });

        foreach ($data as $key=>$value) {
            $this->assertEquals($value, $collection[$key]);
        }
    }

    /**
     * @dataProvider iterables
     * @param $data
     * @test
     */
    public function checkEachEntryInTheCollection($data): void
    {
        $collection = new Collection($data);
        $collection->each(function ($value, $key) use ($data) {
            $this->assertEquals($data[$key], $value);
        });
    }

    /**
     * @dataProvider iterables
     * @param $data
     * @test
     */
    public function reduceTheCollectionToAString($data): void
    {
        $collection = new Collection($data);
        $reduced = $collection->reduce(function($carry, $value) {
            return $carry . json_encode($value);
        }, '');

        $foreached = '';
        foreach ($data as $value) {
            $foreached .= json_encode($value);
        }

        $this->assertEquals($foreached, $reduced);
    }

    /**
     * @dataProvider iterables
     * @param $data
     * @test
     */
    public function filterTheCollection($data): void
    {
        $collection = new Collection($data);
        $filtered = $collection->filter();
        if (0 === count($filtered)) {
            // Empty values were all removed
            $this->assertTrue(true);
        }
        foreach ($filtered as $item) {
            $this->assertNotEmpty($item);
        }
    }

    /**
     * @dataProvider iterables
     * @param $data
     * @test
     */
    public function filterTheCollectionWithACallable($data): void
    {
        $collection = new Collection($data);
        $filtered = $collection->filter(fn ($value) => !empty($value));
        if (0 === count($filtered)) {
            // Empty values were all removed
            $this->assertTrue(true);
        }
        foreach ($filtered as $item) {
            $this->assertNotEmpty($item);
        }
    }

    /**
     * @test
     */
    public function filterOutOddNumbers(): void
    {
        $collection = new Collection(range(1, 200));
        $filtered = $collection->filter(fn ($value) => $value % 2 === 0);
        foreach ($filtered as $even) {
            $this->assertTrue(is_int($even / 2));
        }
    }

    /**
     * @dataProvider iterables
     * @param $data
     * @test
     */
    public function getFirstValue($data): void
    {
        $collection = new Collection($data);
        $first = $collection->first();
        $expected = null;
        foreach ($data as $item) {
            if (!empty($item)) {
                $expected = $expected ?: $item;
            }
        }
        $this->assertEquals($expected, $first);
    }

    /**
     * @test
     */
    public function getFirstValueByCallback(): void
    {
        $collection = new Collection([1,2,3,4,5,6,7,8,9]);
        $first = $collection->first(fn ($value) => $value >= 4);
        $this->assertEquals($first, 4);
    }

    /**
     * @dataProvider iterables
     * @param $data
     * @test
     */
    public function getLastValue($data): void
    {
        $collection = new Collection($data);
        $last = $collection->last();
        $expected = null;
        foreach ($data as $item) {
            if (!empty($item)) {
                $expected = $item;
            }
        }
        $this->assertEquals($expected, $last);
    }

    /**
     * @test
     */
    public function getLastValueByCallback(): void
    {
        $collection = new Collection([1,2,3,4,5,6,7,8,9]);
        $last = $collection->last(fn ($value) => $value < 5);
        $this->assertEquals($last, 4);
    }

    /**
     * @dataProvider iterables
     * @param $data
     * @test
     */
    public function containsValue($data): void
    {
        $collection = new Collection($data);
        $data = (array)$data;
        $randomValue = array_rand($data);
        $this->assertTrue($collection->contains($data[$randomValue]));
    }

    /**
     * @test
     */
    public function containsValueByCallback(): void
    {
        $collection = new Collection([1,2,3,4,5,6,7,8,9]);
        $this->assertTrue($collection->contains(fn ($value) => $value === 4));
    }

    /**
     * @test
     */
    public function pushAndPop(): void
    {
        $collection = new Collection;
        $this->assertCount(0, $collection);
        $collection->push(5);
        $this->assertCount(1, $collection);
        $this->assertEquals(5, $collection->first());
        $this->assertEquals(5, $collection->last());
        $collection->push('test');
        $this->assertCount(2, $collection);
        $this->assertEquals(5, $collection->first());
        $this->assertEquals('test', $collection->last());
        $collection->push('foo', 'bar');
        $this->assertCount(4, $collection);
        $this->assertEquals(5, $collection->first());
        $this->assertEquals('bar', $collection->last());
        $this->assertTrue($collection->contains('test'));
        $popped = $collection->pop();
        $this->assertEquals('bar', $popped);
        $this->assertCount(3, $collection);
        $this->assertEquals(5, $collection->first());
        $this->assertEquals('foo', $collection->last());
        $popped = $collection->pop();
        $this->assertEquals('foo', $popped);
        $this->assertCount(2, $collection);
    }

    /**
     * @test
     */
    public function mergeTwoCollections(): void
    {
        $start = new Collection(range(1, 10));
        $this->assertCount(10, $start);
        $end = new Collection(range(21, 30));
        $this->assertCount(10, $end);
        $merged = $start->merge($end);
        $this->assertCount(20, $merged);
        $this->assertEquals(1, $merged->first());
        $this->assertEquals(30, $merged->last());
    }

    /**
     * @dataProvider sorts
     * @param $data
     * @param $sorted
     * @test
     */
    public function simpleSort($data, $sorted): void
    {
        $collection = new Collection($data);
        $collectionSorted = $collection->sort();
        foreach ($sorted as $key => $val) {
            $this->assertEquals($val, $collectionSorted[$key]);
        }
    }

    /**
     * @dataProvider sorts
     * @param $data
     * @param $sorted
     * @test
     */
    public function userSort($data, $sorted): void
    {
        $collection = new Collection($data);
        $collectionSorted = $collection->usort(fn ($a, $b) => $a <=> $b);
        foreach ($sorted as $key => $val) {
            $this->assertEquals($val, $collectionSorted[$key]);
        }
    }

    /**
     * @dataProvider sorts
     * @param $data
     * @param $sorted
     * @test
     */
    public function simpleSortTypedCollection($data, $sorted): void
    {
        $collection = Collection::newTypedValueCollection(gettype(current($data)), $data);
        $collectionSorted = $collection->sort();
        foreach ($sorted as $key => $val) {
            $this->assertEquals($val, $collectionSorted[$key]);
        }
    }

    /**
     * @dataProvider sorts
     * @param $data
     * @param $sorted
     * @test
     */
    public function userSortTypedCollection($data, $sorted): void
    {
        $collection = Collection::newTypedValueCollection(gettype(current($data)), $data);
        $collectionSorted = $collection->usort(fn ($a, $b) => $a <=> $b);
        foreach ($sorted as $key => $val) {
            $this->assertEquals($val, $collectionSorted[$key]);
        }
    }

    /**
     * @dataProvider sorts
     * @param $data
     * @param $sorted
     * @test
     */
    public function simpleSortTypedKeyCollection($data, $sorted): void
    {
        $collection = Collection::newTypedKeyCollection('integer', $data);
        $collectionSorted = $collection->sort();
        $collectionArray = array_values($collectionSorted->getArrayCopy());
        foreach ($collectionArray as $key=>$value) {
            $this->assertEquals($value, $sorted[$key]);
        }
    }

    /**
     * @dataProvider sorts
     * @param $data
     * @param $sorted
     * @test
     */
    public function userSortTypedKeyCollection($data, $sorted): void
    {
        $collection = Collection::newTypedKeyCollection('integer', $data);
        $collectionSorted = $collection->usort(fn ($a, $b) => $a <=> $b);
        $collectionArray = array_values($collectionSorted->getArrayCopy());
        foreach ($collectionArray as $key=>$value) {
            $this->assertEquals($value, $sorted[$key]);
        }
    }

    /**
     * @dataProvider asorts
     * @param $data
     * @param $sorted
     * @test
     */
    public function simpleAsort($data, $sorted): void
    {
        $collection = new Collection($data);
        $collectionSorted = $collection->asort();
        foreach ($collectionSorted as $key => $value) {
            $this->assertEquals($value, $sorted[$key]);
        }
    }

    /**
     * @dataProvider asorts
     * @param $data
     * @param $sorted
     * @test
     */
    public function userAsort($data, $sorted): void
    {
        $collection = new Collection($data);
        $collectionSorted = $collection->uasort(fn ($a, $b) => $a <=> $b);
        foreach ($collectionSorted as $key => $value) {
            $this->assertEquals($value, $sorted[$key]);
        }
    }

    /**
     * @dataProvider sorts
     * @param $data
     * @test
     */
    public function shuffle($data): void
    {
        $collection = new Collection($data);
        for ($i=0; $i<5; $i++) {
            // Sometimes we can shuffle to the original config, so give us a couple of goes to get it right
            $shuffled = $collection->shuffle();
            $shuffledArray = $shuffled->getArrayCopy();
            if ($shuffledArray !== $data) {
                break;
            }
        }

        $this->assertNotEquals($shuffledArray, $data);
    }

    /**
     * @dataProvider shuffles
     * @param $data array
     * @param $seed string|int
     * @param $expected array
     * @test
     */
    public function shuffleWithSeed($data, $seed, $expected): void
    {
        $collection = new Collection($data);
        $shuffled = $collection->shuffle($seed);
        $shuffledArray = $shuffled->getArrayCopy();
        $this->assertNotEquals($shuffledArray, $data);
        $this->assertEquals($shuffledArray, $expected);
    }

    /**
     * @test
     */
    public function collectInto(): void
    {
        $collection1 = new Collection([1,2,3,4,5]);
        $collection2 = $collection1->into(Collection::class);
        $this->assertEquals($collection1, $collection2);
        $this->assertNotEquals(spl_object_hash($collection1), spl_object_hash($collection2));
        $this->assertInstanceOf(Collection::class, $collection2);
    }

    /**
     * @test
     */
    public function failToCollectIntoANonCollectionType(): void
    {
        $this->expectException(\TypeError::class);
        $collection = new Collection([1,2,3,4,5]);
        $collection->into(\DateTime::class);
    }

    /**
     * @test
     */
    public function failToCollectIntoANonExitingType(): void
    {
        $this->expectException(\TypeError::class);
        $collection = new Collection([1,2,3,4,5]);
        $collection->into('my arbitrary type');
    }

    /**
     * @test
     */
    public function mapInto(): void
    {
        $stringCollection = new class extends Collection
        {
            protected ?string $valueType = 'string';
        };

        $intCollection = new class ([1,2,3,4,5]) extends Collection
        {
            protected ?string $valueType = 'integer';
        };

        $finalCollection = $intCollection->mapInto(
            fn (int $int): string => (string) ($int * 10),
            get_class($stringCollection),
        );

        $finalCollection->each(function ($value, $key) use ($intCollection) {
            $this->assertSame((string) ($intCollection[$key] * 10), $value);
        });
    }

    /**
     * @test
     */
    public function mapIntoThrows(): void
    {
        $this->expectException(\TypeError::class);
        $intCollection = new class ([1,2,3,4,5]) extends Collection
        {
            protected ?string $valueType = 'integer';
        };

        $intCollection->mapInto(
            fn (int $int): string => (string) ($int * 10),
            get_class($intCollection)
        );
    }

    /**
     * @test
     */
    public function slice(): void
    {
        $collection = new Collection(['a' => 1, 'b' => 2, 'c' => 3]);

        $this->assertSame(['a' => 1, 'b' => 2], $collection->slice(0, 2)->getArrayCopy());
    }

    /**
     * @dataProvider collectibles
     * @param $data
     * @test
     */
    public function collectionWillJsonSerialize($data): void
    {
        $collection = new Collection($data);
        $json = json_encode($collection);
        $this->assertIsString($json);
        $this->assertIsArray($collection->jsonSerialize());
    }

    /**
     * @test
     */
    public function checkForEmptyCollection(): void
    {
        $collection = new Collection();

        $this->assertTrue($collection->isEmpty());
        $this->assertFalse($collection->isNotEmpty());
    }

    /**
     * @test
     */
    public function checkForNonEmptyCollection(): void
    {
        $collection = new Collection([1]);

        $this->assertTrue($collection->isNotEmpty());
        $this->assertFalse($collection->isEmpty());
    }

    /** @test */
    public function keyStrategyNotSetWorksOnConstruction(): void
    {
        $collection = new Collection([1]);

        $this->assertSame($collection[0], 1);
    }

    /** @test */
    public function keyStrategyNotSetWorksWithAppend(): void
    {
        $collection = new Collection();
        $collection->append(1);

        $this->assertSame($collection[0], 1);
    }

    /** @test */
    public function keyStrategyNotSetWorksWithOffsetSet(): void
    {
        $collection = new Collection();
        $collection[] = 1;

        $this->assertSame($collection[0], 1);
    }

    /** @test */
    public function keyStrategySetWorksOnConstruction(): void
    {
        $items = [
            ['id' => 1234, 'name' => 'Erica'],
            ['id' => 9999, 'name' => 'Bob'],
        ];

        $collection = new class ($items) extends Collection {
            protected function keyStrategy(mixed $value): mixed
            {
                return $value['id'];
            }
        };

        $this->assertSame($collection[1234], ['id' => 1234, 'name' => 'Erica']);
        $this->assertSame($collection[9999], ['id' => 9999, 'name' => 'Bob']);
    }

    /** @test */
    public function keyStrategySetWorksOnAppend(): void
    {
        $collection = new class() extends Collection {
            protected function keyStrategy(mixed $value): mixed
            {
                return $value['id'];
            }
        };

        $collection->append(['id' => 1234, 'name' => 'Erica']);
        $collection->append(['id' => 9999, 'name' => 'Bob']);

        $this->assertSame($collection[1234], ['id' => 1234, 'name' => 'Erica']);
        $this->assertSame($collection[9999], ['id' => 9999, 'name' => 'Bob']);
    }

    /** @test */
    public function keyStrategySetWorksOnOffsetSet(): void
    {
        $collection = new class() extends Collection {
            protected function keyStrategy(mixed $value): mixed
            {
                return $value['id'];
            }
        };

        $collection[] = ['id' => 1234, 'name' => 'Erica'];
        $collection[] = ['id' => 9999, 'name' => 'Bob'];

        $this->assertSame($collection[1234], ['id' => 1234, 'name' => 'Erica']);
        $this->assertSame($collection[9999], ['id' => 9999, 'name' => 'Bob']);
    }

    /** @test */
    public function itImplodesStrings(): void
    {
        $collection = new Collection(['a', 'b']);
        $this->assertSame('a, b', $collection->implode(', '));
    }

    /** @test */
    public function itImplodesObjects(): void
    {
        $newClass = function(string $foo) {
            return new class($foo) {
                private $foo;
                public function __construct(string $foo) {
                    $this->foo = $foo;
                }
                public function foo(): string {
                    return $this->foo;
                }
            };
        };
        $collection = new Collection([
            $newClass('foo'),
            $newClass('bar'),
            $newClass('baz')
        ]);
        $this->assertSame(
            'foo, bar, baz',
            $collection->implode(', ', fn ($item) => $item->foo())
        );
    }





    /**
     * Get a Strict Typed Collection
     *
     * Set the key and value types to enforce strict types within the collection
     *
     * @param mixed $data
     * @param string $keyType
     * @param string $valueType
     * @return Collection
     */
    private function getTypedCollection($data, string $keyType=null, string $valueType=null): Collection
    {
        return Collection::newTypedCollection($keyType, $valueType, $data);
    }

    public function collectibles(): array
    {
        return [
            [[[],[],[],[]], null, 'array'],
            [[8,9,3,4,1,6,2,10,9,5,7], null, 'integer'],
            [['f','b','e','c','d','a'], 'integer', 'string'],
            [new Collection([4,3,5,1,2,6]), 'integer', null],
            [new class implements \JsonSerializable, \Countable { public function count(){ return count($this->jsonSerialize()); } public function jsonSerialize(){ return ['a','b','c','d','e','f']; }}],
        ];
    }

    public function iterables(): array
    {
        $ret = [];
        foreach ($this->collectibles() as $case) {
            if (is_iterable(current($case))) {
                $ret[] = $case;
            }
        }
        return $ret;
    }

    public function arrays(): array
    {
        $ret = [];
        foreach ($this->collectibles() as $case) {
            if (is_array(current($case))) {
                $ret[] = $case;
            }
        }
        return $ret;
    }

    public function mismatchedTypedCollections(): array
    {
        return [
            [[1,2,3,4,5,6,7,8,9,0], 'string', 'string'],
            [[1,2,3,4,5,6,7,8,9,0], 'integer', 'string'],
            [[1,2,3,4,5,6,7,8,9,0], 'string', 'integer'],
            [[1,2,3,4,5,6,7,8,9,0], 'string', null],
            [[1,2,3,4,5,6,7,8,9,0], null, 'string'],
            [[new \DateTime, new \StdClass], \DateTime::class, 'int'],
            [[new \DateTime, new \StdClass], null, \DateTime::class],
            [['a'=>'b', 'c'=>'d'], 'integer', null],
            [['a'=>'b', 'c'=>'d'], null, 'integer'],
        ];
    }

    public function shuffles(): array
    {
        return [
            [[1,2,3,4,5,6,7,8,9,10], 15, [9,10,2,1,7,6,8,5,4,3]],
            [[1,2,3,4,5,6,7,8,9,10], PHP_INT_MAX, [7,10,8,3,6,1,9,5,4,2]],
        ];
    }

    public function sorts(): array
    {
        return [
            [[10,1,4,2,5,9,8,6,3,7], [1,2,3,4,5,6,7,8,9,10]],
            [['aa', 'ba', 'ab', 'b', 'a'], ['a','aa', 'ab', 'b', 'ba']],
        ];
    }

    public function asorts(): array
    {
        return [
            [['a'=>3, 'b'=>2, 'c'=>1], ['c'=>1, 'b'=>2, 'a'=>3]],
            [['a'=>'c', 'b'=>'b', 'c'=>'a'], ['c'=>'a', 'b'=>'b', 'a'=>'c']],
        ];
    }
}
