# MicroDB

[![Build Status](https://travis-ci.org/web-complete/microDb.svg?branch=master)](https://travis-ci.org/web-complete/microDb)
[![Coverage Status](https://coveralls.io/repos/github/web-complete/microDb/badge.svg?branch=master)](https://coveralls.io/github/web-complete/microDb?branch=master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/web-complete/microDb/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/web-complete/microDb/?branch=master)
[![Latest Stable Version](https://poser.pugx.org/web-complete/microDb/version)](https://packagist.org/packages/web-complete/microDb)
[![License](https://poser.pugx.org/web-complete/microDb/license)](https://packagist.org/packages/web-complete/microDb)

Tiny schemaless file DB library with no dependencies. Most suitable for small sites and fast prototyping

## Installation

```
composer require web-complete/microDb
```

## Usage

#### create client

```php
$microDb = new MicroDb(__DIR__ . '/storage', 'mydb1');
```

- you can switch it to runtime in-memory storage (for example, in tests)

```php
$microDb->setType('runtime');
```

#### get collection

Think about collection as a table in mysql-world

```php
$usersCollection = $microDb->getCollection('users');
```

#### insert item

```php
$id = $usersCollection->insert(['name' => 'John Smith', 'some_data' => [1,2,3]]);
```

Collection will assign the item a new id. Default id field is "id", but you can use any you wish instead:

```php
$id = $usersCollection->insert(['name' => 'John Smith', 'some_data' => [1,2,3]], "uid");
```

#### batch insert

Insert many items in one transaction

```php
$usersCollection->insertBatch(
    ['name' => 'John Smith 1', 'some_data' => [1,2,3]],
    ['name' => 'John Smith 2', 'some_data' => [3,4,5]],
    ['name' => 'John Smith 3', 'some_data' => [5,6,7]],
);
```

#### update item

```php
$filter = function ($item) {
    return $item['id'] == 2;
};
$usersCollection->update($filter, ['name' => 'John Smith 2 updated']);
```

update can affect many items as well: 

```php
$filter = function ($item) {
    return $item['last_visit'] < $newYear;
};
$usersCollection->update($filter, ['active' => false]);
```

#### delete item

delete one or more items by filter

```php
$filter = function ($item) {
    return $item['id'] == 2;
};
$usersCollection->delete($filter);
```

#### fetch many items

```php
$filter = function ($item) {
    return $item['active'] == true;
};
$activeUsers = $usersCollection->fetchAll($filter);
```

or with sorting:

```php
$filter = function ($item) {
    return $item['active'] == true;
};
$sort = function ($item1, $item2) {
    return $item1['last_visit'] <=> $item2['last_visit'];
};
$activeUsers = $usersCollection->fetchAll($filter, $sort);
```

and limit 20, offset 100:

```php
...
$activeUsers = $usersCollection->fetchAll($filter, $sort, 20, 100);
```

#### fetch one item

The same syntax as fetchAll (without limit, offset), but returns one item or null

```php
...
$activeUser = $usersCollection->fetchOne($filter, $sort);
```

Find by id:

```php
$user = $usersCollection->fetchOne(function ($item) {
    return $item['id'] == 15;
});
```

#### drop collection

```php
$collection->drop();
```

#### runtime storage

Runtime storage has 2 useful static methods to use in testing:
- **StorageRuntime::dump()** - returns current storage stage
- **StorageRuntime::clear()** - clears runtime storage
