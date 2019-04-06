# PHP Collection Library
Works with arrays and offers you a bunch of features:
- Order collections with several sort descriptors
````php
$collection = new DefaultCollection(
  [
    "echo",
    "alpha",
    "delta",
    "charlie",
    "bravo"
  ]
);
$collection->sort([
  new DefaultSortDescriptor(true) // Sort ascending
]);

print_r( $collection->toArray() ); // [alpha, bravo, charlie, delta, echo]
````
- Create tagged collections, so each element can have tags
````php
$collection = new TaggedCollection([1, 2, 3], /* accept duplicates */ true, /*case sensitive*/ true, /* tags ...*/ "test", "haha");

print_r($collection["test"]); // [1, 2, 3]
print_r($collection["unexisting tag"]); // NULL -- without notice
````
- Create priority collections that are ordered automatically against element priority
````php
$collection = new PriorityCollection();
$collection->add(3, [5, 7]);
$collection->add(1, 8, 7);
$collection->add(7, 11, 3);


print_r($collection->toArray()); // [8, 7, [5, 7], 11, 3]
````
- Create dependency collection, elements can depend on others
```php
$collection = new DependencyCollection();
$collection->add('thomas', 1, ["Foo", 'Bar']);
$collection->add('Foo', 2);
$collection->add("Bar", 3, ["Foo"]);
$collection->add("Bettina", 6, ["thomas"]);

print_r($collection->getElementDependencies("thomas")); // [Foo, Bar]
print_r($collection->getOrderedElements());
/*
[
  Foo => 1,
  Bar => 3,
  thomas => 1
  Bettina => 6
]
*/
```
Simply install it with composer:
````bin
$ composer require tasoft/collection
````
