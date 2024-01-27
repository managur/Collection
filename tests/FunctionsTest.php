<?php declare(strict_types=1);

namespace tests;

use Managur\Collection\Collection;
use PHPUnit\Framework\TestCase;

final class FunctionsTest extends TestCase
{
    /**
     * @dataProvider collectibles
     * @param $data
     * @test
     */
    public function functions($data): void
    {
        $collection = collect($data);
        $this->assertInstanceOf(Collection::class, $collection);
        $this->assertCount(count($data), $collection);

        $collection = collectInto(Collection::class, $data);
        $this->assertInstanceOf(Collection::class, $collection);
        $this->assertCount(count($data), $collection);
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
}
