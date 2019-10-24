<?php

require __DIR__ . '/vendor/autoload.php';

ini_set('memory_limit', -1);

class A {}

class ACollection extends \Managur\Collection\Collection
{
    protected $valueType = A::class;
}

$a = new A;
$collection = new ACollection();

echo "Object performance\n";

$start = microtime(true);

for ($i = 0; $i < 5000000; ++$i) {
    $collection->append($a);
}

$end = microtime(true);

$duration = $end - $start;

echo "Time: $duration\n";

class BoolCollection extends \Managur\Collection\Collection
{
    protected $valueType = 'boolean';
}

$collection = new BoolCollection();

echo "Scalar performance\n";

$start = microtime(true);

for ($i = 0; $i < 5000000; ++$i) {
    $collection->append(true);
}

$end = microtime(true);

$duration = $end - $start;

echo "Time: $duration\n";
