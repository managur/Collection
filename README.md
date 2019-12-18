# ![Managur](managur_logo.png)<br>Collections

[![Build Status](https://travis-ci.org/managur/Collection.svg?branch=master)](https://travis-ci.org/managur/Collection)
[![Latest Stable Version](https://poser.pugx.org/managur/collection/v/stable)](https://packagist.org/packages/managur/collection)
[![License](https://poser.pugx.org/managur/collection/license)](https://packagist.org/packages/managur/collection)
[![Coverage Status](https://coveralls.io/repos/github/managur/Collection/badge.svg)](https://coveralls.io/github/managur/Collection)
[![Maintainability](https://api.codeclimate.com/v1/badges/cf9fc22664d7eca42514/maintainability)](https://codeclimate.com/github/managur/Collection/maintainability)

_Managur Collections_ is a library that provides a fully featured collection
object to PHP.

## What are collections?
Collections are similar to an array in PHP, but can be restricted to a specific
type, both at the index and value level.

Collections also provide some built in functionality, such as being able to
easily search the contents for a particular value, perform filtering, and to
iterate through within a closed scope.

Let's take a look at some examples:

## Installation
Before we can use Managur Collections, we need to install it. The recommended
method is using PHP's package manager, Composer:

```sh
$ composer require managur/collection
```

## Example Usage
_We'll assume that you have installed via Composer, and therefore are using
Composer's autoloader._

There are two ways in that you can use Managur Collections. The first is using
its class constructor, as follows:
```php
<?php
use Managur\Collection\Collection;

$collection = new Collection(['your', 'collectible', 'data']);
```

The second way is to use the handy `collect()` function which is included:
```php
$collection = collect(['your', 'collectible', 'data']);
```

And now you have a collection!

## Restricting Key and Value types
Collections are great for type guarantees. If you're passing an array around,
there are no guarantees that the array indexes or values are of any given type,
which means that you will regularly need to check them around your application.

Managur Collections are easily constrained to a type, either by extending the
base `Collection` class yourself, or by using the provided factory methods.

### Integer Collection
If you need a collection that can ONLY have integer values, you can extend
Managur Collections with your own class as follows:

```php
<?php declare(strict_types=1);

use Managur\Collection\Collection;

final class IntegerCollection extends Collection
{
    protected $valueType = 'integer';
}
```
Now, when you construct or append data to your `IntegerCollection`, if the data
that you're trying to add to the collection isn't of the type `Integer`, a
`TypeError` exception will be thrown which you can catch immediately.

Fail fast.

### Miscellaneous Collection with Integer Keys
Similar to enforcing a type for the values, you can also fix the index (or key)
types:
```php
<?php declare(strict_types=1);

use Managur\Collection\Collection;

final class IntegerKeyCollection extends Collection
{
    protected $keyType = 'integer';
}
```
It is now impossible to use an index that is not an integer for this specific
collection type.

You can combine the key and value restrictions to force a very specifically
typed collection if you require.

### Key Strategies
When you `append()` or `offsetSet()` a value to the collection, we call the
protected `keyStrategy()` method, passing it the item that you want to collect.
By default we return `null`, which tells `append()` and `offsetSet()` that you
wish for the collection to use PHP’s default zero-indexed, auto-incrementing
key IDs.

If this is not the case, whether because you simply wish to organise by your
own keys, or because you’ve restricted the key type to a `string`, for example,
you can override the `keyStrategy()` method in your child collection class to
automatically set the key to a value of your choosing.

For example, in our hypothetical `UserCollection` class, we add this
implementation of `keyStrategy()`:

```php
protected function keyStrategy($value)
{
    return $value->id();
}
```

And once the key strategy is set, appending or setting items in the collection
will have the key set appropriately.

```php
$user = new User('my-user-id', 'Managur User');
$collection = new UserCollection();
$collection[] = $user;

var_dump($collection['my-user-id'] === $user); // returns true
```

### Using Static Factory Methods to Dynamically Fix Typed Collections
We provide three methods that will generate an anonymous class with the same
functionality as the main collection, but with enforced types for keys and/or
values:

```php
use Managur\Collection\Collection;

$integerValueCollection = Collection::newTypedValueCollection('integer', [1,2,3,4,5]);
$integerKeyCollection = Collection::newTypedKeyCollection('integer', ['a','b',1]);
$integerCollection = Collection::newTypedCollection('integer', 'integer', [1,2,3,4,5]);
```
If you wish, you can use the `newTypedCollection()` method for all purposes,
passing in `null` for the key (first argument) and/or the value (second
argument) to get the specific combination that you require.

## Tests
This library uses PHPUnit 7 to provide unit tests. To run the tests yourself,
simply enter the following at a command line interface:
```sh
$ composer test
```
Tests _should_ run automatically, assuming that you can call PHP directly from
the command line (ie it is in your path).

We also automate testing with Travis-CI. You can view the latest builds
[here](https://travis-ci.org/managur/Collection).

## Code Sniffer
We enforce PSR-2 coding standards. To test the code, run this command:
```sh
$ composer cs
```

## Fix Code Standard Failures
To automatically fix code standard failures, run this command:
```sh
$ composer cs-fix
```

## Manual
Full functionality provided with Managur Collection is documented here:

### Appending Data
**Important**: Appending will fail if you are using typed indexes

Data can be appended to a collection in one of two ways:

#### Array Syntax
```php
$collection[] = 'new data';
```

#### Object Syntax
```php
$collection->append('new data');
```

### Offset Set
If you need to set a value to a specific index within the collection, do so as
follows:

**Note**: If there was already data at this index, it will be overwritten

#### Array Syntax
```php
$collection[4] = 'new data';
```

#### Object Syntax
```php
$collection->offsetSet(4, 'new data');
```

### Map
`map()` provides a method to iterate over the collection, modifying them and
returning a new collection with the changes. It will accept any `callable` as a
parameter.

Because this function operates within a private scope, it protects any
variables that exist outside of the _loop_, as well as preventing data within
its scope from leaking into your wider code. For this reason, `map()` is
recommended over a simple `foreach()` loop in most cases.

Here's a simple example of doubling the values of each value:
```php
$collection = new Collection([1, 2, 3, 4, 5, 6, 7, 8, 9, 10]);
$doubles = $collection->map(function ($value) {
    return $value * 2;
});
```

### Map Into
The `mapInto()` method allows you to take any collection and in one call create
a new collection of a given type, run a callable across the data, and have it
immediately collected into the new collection.

Here's an example where we have created new collections that are restricted to
allowing only integers to be collected in the first, and strings in the second:
```php
$ints = new IntegerCollection([1, 2, 3, 4, 5]);
$strings = $ints->mapInto(function (int $item): string {
    return (string)($item*10);
}, StringCollection::class);
```
We now have a `StringCollection` which contains a string representation of
every value from the original `$ints` collection, multiplied by 10.

### Slice
`slice()` provides a method to return the sequence of elements from the array

This works on the position in the array, *not* the key.

Here's a simple example slicing the first 2 elements from the array:
```php
$collection = new Collection(['a' => 1, 'b' => 2, 'c' => 3]);
$doubles = $collection->slice(0, 2); // ['a' => 1, 'b' => 2]
```

The [PHP documentation](https://www.php.net/manual/en/function.array-slice.php)
has a full description of the way `$offset` and `$limit` behave.

### Each
Sometimes we need to iterate over values, but we don't want to make any
changes. That is where `each()` is different to `map()`. Otherwise, the
functionality is identical.

Referring back to the `map()` example, here is an implementation of `each()`
where we simply `echo` the doubled value, instead:
```php
$collection = new Collection([1, 2, 3, 4, 5, 6, 7, 8, 9, 10]);
$collection->each(function ($value) {
    echo $value * 2;
});
```

### Reduce
We often loop over data to concatenate results. Maybe we want to get a list of
all email addresses from a collection of users. This is where `reduce()` is our
go-to method. You can reduce into anything.

The `reduce()` method requires a callback which takes two arguments; the
_carry_ variable that we'll be appending to in each iteration, and the value
that we'll be using.

`reduce()` takes a final argument, which is the initialising _carry_ value.
This is optional, in which case `null` will be passed into your callback in the
first iteration, so ensure that your callback will accept a null value. We do
not recommend this, however, and suggest that you always provide an initial
value of the same type that you want to be returned when the reduction is
completed.

In this example, we'll pass in an empty array as our initial value, as you can
see on the last line:
```php
$collection = new Collection($arrayOfUserObjects);
$emails = $collection->reduce(function ($carry, $user) {
    $carry[] = $user->email();
    return $carry
}, []);
```
It's common for people to forget to apply the value to `$carry` and return
`$carry` at the end. Don't forget this, else you'll find that everything
reduces to `null`.

### Filter
If you need to remove values from your collection, you can do so using the
`filter()` method. If you call it without any arguments then any values that
match the same criteria that the core `empty()` function returns `true` for,
will be removed from the results.

You can optionally provide a callback that should return `true` if the data
should be retained, or `false` to be filtered out.

**Note**: `$filter()` returns a new copy of the collection without the filtered
values. It does **not** filter the original collection.

Here's an example where we will filter out all inactive users from our user
collection:
```php
$collection = new Collection($arrayOfUserObjects);
$filtered = $collection->filter(function ($user) {
    return $user->isActive();
});
```
Nice and simple, and again we're protecting scope so that we're not
accidentally overwriting anything in our own code.

### First
The `first()` method optionally takes a callback as an argument. If you do not
provide this then it will return the first non-empty value from the collection.

If you _do_ provide a callback, it will return the first value that matches
the callback's criteria by returning true.

Let's look at an example where we get the first user who has a Gmail email
address:
```php
$collection = new Collection($arrayOfUserObjects);
$firstGmailUser = $collection->first(function ($user) {
    return false !== stripos($user->emailAddress(), '@gmail.');
});
```

### Last
`last()` is exactly the same as `first()`, with the (hopefully obvious)
exception that it returns the last entry:
```php
$collection = new Collection($arrayOfUserObjects);
$lastGmailUser = $collection->last(function ($user) {
    return false !== stripos($user->emailAddress(), '@gmail.');
});
```

### Contains
If you need to check whether a specific value exists within your collection,
this is the method for you. You can also search with a callback, providing you
with a really flexible search operation where you can match on partial or
computed data.

`contains()` returns a boolean value, so you can use this to check conditions.
```php
$collection = new Collection($arrayOfUserObjects);
$containsAGmailUser = $collection->contains(function ($user) {
    return false !== stripos($user->emailAddress(), '@gmail.');
});
```
If a user with a Gmail address is found in the collection then
`$containsAGmailUser` will be `true`. 

### Empty
The methods `isEmpty()` and `isNotEmpty()` are available to check whether the
collection contains items or not.

```php
$collection = new Collection([]);
$collection->isEmpty(); // true
```

```php
$collection = new Collection([1, 2, 3]);
$collection->isNotEmpty(); // true
```

### Push
The `push()` method is similar to `append()`, in that it will only work if you
have not defined a strict index type. The only way that it is different is that
you can push multiple values in one operation:
```php
$collection->push(5, 6, 7, 8, 9);
```

### Pop
`pop()` is a typical pop method, where the latest value will be returned, while
simultaneously removed from the collection:
```php
$collection = new Collection([1, 2, 3, 4, 5]);
$popped = $collection->pop();
```
Now, `$popped` will equal `5`, and the collection itself will now only contain
the numbers 1 through 4.

### Merge
If you have two compatible collections (ie they're not restricted to different
types), you can merge them in one operation:
```php
$collection1 = new Collection([1,2,3,4,5]);
$collection2 = new Collection([6,7,8,9,10]);
$merged = $collection1->merge($collection2);
```
Note that a brand new collection is returned, so both `$collection1` and
`$collection2` will remain as they were before.

### Into
The `into()` method provides a readable method to copy the collection into
another _compatible_ collection:
```php
$collection1 = new Collection([1,2,3,4,5]);
$collection2 = $collection1->into(IntegerCollection::class);
```
There is also a provided function that collects directly into your preferred
collection class:
```php
$collection = collectInto(IntegerCollection::class, [1,2,3,4,5]);
```

### Sort
If you need to sort your collection data, this is the method for you.

`sort()` will accept a callable if you wish to use a user-defined sorting
algorithm, or you can call it with no arguments.

This is analogous to using the `sort()` and `usort()` functions with a simple
array.

If you have specified a fixed index type then we will maintain the indexes for
each value, similar to calling `asort()` or `uasort()` on a normal array.

```php
$collection = new Collection([5,2,6,4,7,1]);
$sorted = $collection->sort();
$alsoSorted = $collection->sort(function ($a, $b) {
    return $a <=> $b;
});
```
This method will return a copy of the collection with the sorting applied.

### ASort
Similar to `sort()`, the `asort()` method optionally accepts a callback to sort
by a custom algorithm. The only difference between `sort()` and `asort()` is
that index associations are maintained, regardless of whether the collection
key type is constrained or not.
```php
$collection = new Collection([5,2,6,4,7,1]);
$sorted = $collection->asort();
$alsoSorted = $collection->asort(function ($a, $b) {
    return $a <=> $b;
});
```
This method will return a copy of the collection with the sorting applied.

### Shuffle
The `shuffle()` method will take your values, shuffle them randomly, and then
return a new instance of the collection with the shuffled data:
```php
$collection = new Collection([1,2,3,4,5]);
$shuffled = $collection->shuffle();
```
`shuffle()` also takes an optional integer _seed_, which will be used to
determine the start state of the pseudo random number generator (`mt_rand()`)
that is used to determine the order that elements are shuffled into.

If you use the _seed_ with `shuffle()`, the resulting collection will always
have its elements in the same order for a given original collection and seed
value. It will never return a different order for this combination.

### Implode
The `implode()` method will take your collection and return a string with the items joined together with `$glue`
```php
$collection = new Collection(['a', 'b']);
$joined = $collection->implode(', '); // 'a, b'
```
