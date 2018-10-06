<?php declare(strict_types=1);

namespace tests;

use Managur\Collection\Collection;
use PHPUnit\Framework\TestCase;

final class CollectionTest extends TestCase
{
    /**
     * @dataProvider collectibles
     * @param mixed $data
     * @test
     */
    public function basicTypeAndCount($data)
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
    public function appendByMethod($data)
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
    public function appendLikeArray($data)
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
    public function offsetSetByMethod($data)
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
    public function offsetSetLikeArray($data)
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
    public function typedCollections($data, $keyType=null, $valueType=null)
    {
        $collection = $this->getTypedCollection($data, $keyType, $valueType);
        foreach ($data as $key=>$val) {
            $this->assertEquals($val, $collection[$key]);
        }
        $this->assertEquals(count($data), count($collection));
    }

    /**
     * @dataProvider mismatchedTypedCollections
     * @expectedException \TypeError
     * @param $data
     * @param $keyType
     * @param $valueType
     * @test
     */
    public function typedCollectionsWithIncorrectTypes($data, $keyType, $valueType)
    {
        $this->getTypedCollection($data,$keyType, $valueType);
    }

    /**
     * @dataProvider iterables
     * @param $data
     * @test
     */
    public function checkMapOfCollectionData($data)
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
    public function checkEachEntryInTheCollection($data)
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
    public function reduceTheCollectionToAString($data)
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
    public function filterTheCollection($data)
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
    public function filterTheCollectionWithACallable($data)
    {
        $collection = new Collection($data);
        $filtered = $collection->filter(function ($value) {
            return !empty($value);
        });
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
    public function filterOutOddNumbers()
    {
        $collection = new Collection(range(1, 200));
        $filtered = $collection->filter(function ($value) {
            return $value % 2 === 0;
        });
        foreach ($filtered as $even) {
            $this->assertTrue(is_int($even / 2));
        }
    }

    /**
     * @dataProvider iterables
     * @param $data
     * @test
     */
    public function getFirstValue($data)
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
    public function getFirstValueByCallback()
    {
        $collection = new Collection([1,2,3,4,5,6,7,8,9]);
        $first = $collection->first(function ($value) {
            return $value >= 4;
        });
        $this->assertEquals($first, 4);
    }

    /**
     * @dataProvider iterables
     * @param $data
     * @test
     */
    public function getLastValue($data)
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
    public function getLastValueByCallback()
    {
        $collection = new Collection([1,2,3,4,5,6,7,8,9]);
        $last = $collection->last(function ($value) {
            return $value < 5;
        });
        $this->assertEquals($last, 4);
    }

    /**
     * @dataProvider iterables
     * @param $data
     * @test
     */
    public function containsValue($data)
    {
        $collection = new Collection($data);
        $data = (array)$data;
        $randomValue = array_rand($data);
        $this->assertTrue($collection->contains($data[$randomValue]));
    }

    /**
     * @test
     */
    public function containsValueByCallback()
    {
        $collection = new Collection([1,2,3,4,5,6,7,8,9]);
        $this->assertTrue($collection->contains(function ($value) {
            return $value === 4;
        }));
    }

    /**
     * @test
     */
    public function pushAndPop()
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
    public function mergeTwoCollections()
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
            ['string of text'],
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
}
