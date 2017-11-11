<?php

namespace tests;

use WebComplete\microDb\MicroDb;
use WebComplete\microDb\StorageRuntime;

class MicroDbTest extends PackageTestCase
{

    public function setUp()
    {
        parent::setUp();
        $this->clearDir();
    }

    public function tearDown()
    {
        $this->clearDir();
        parent::tearDown();
    }

    public function testCreateInstance()
    {
        $microDb = new MicroDb(__DIR__ . '/storage', 'test-db');
        $this->assertInstanceOf(MicroDb::class, $microDb);
    }

    public function testInsert()
    {
        $microDb = new MicroDb(__DIR__ . '/storage', 'test-db');
        $collection = $microDb->getCollection('users');
        $this->assertCount(0, $collection->fetchAll());
        $id1 = $collection->insert(['name' => 'user 1', 'some_data' => [1,2,3]]);
        $id2 = $collection->insert(['name' => 'user 2', 'some_data' => [3,4,5]]);
        $this->assertEquals(1, $id1);
        $this->assertEquals(2, $id2);
        $this->assertCount(2, $collection->fetchAll());
        $this->assertEquals([
            ['id' => 1, 'name' => 'user 1', 'some_data' => [1,2,3]],
            ['id' => 2, 'name' => 'user 2', 'some_data' => [3,4,5]]
        ], $collection->fetchAll());
    }

    public function testInsertBatch()
    {
        $microDb = new MicroDb(__DIR__ . '/storage', 'test-db');
        $collection = $microDb->getCollection('users');
        $this->assertCount(0, $collection->fetchAll());
        $collection->insertBatch([
            ['name' => 'user 1', 'some_data' => [1,2,3]],
            ['name' => 'user 2', 'some_data' => [3,4,5]]
        ]);
        $this->assertCount(2, $collection->fetchAll());
        $this->assertEquals([
            ['id' => 1, 'name' => 'user 1', 'some_data' => [1,2,3]],
            ['id' => 2, 'name' => 'user 2', 'some_data' => [3,4,5]]
        ], $collection->fetchAll());
    }

    public function testUpdate()
    {
        $microDb = new MicroDb(__DIR__ . '/storage', 'test-db');
        $collection = $microDb->getCollection('users');
        $this->assertCount(0, $collection->fetchAll());
        $collection->insertBatch([
            ['name' => 'user 1', 'some_data' => [1,2,3]],
            ['name' => 'user 2', 'some_data' => [3,4,5]],
            ['name' => 'user 3', 'some_data' => [5,6,7]],
        ]);
        $collection->update(function ($item) {
            return $item['id'] === 2;
        }, ['name' => 'user 2 updated']);
        $this->assertEquals([
            ['id' => 1, 'name' => 'user 1', 'some_data' => [1,2,3]],
            ['id' => 2, 'name' => 'user 2 updated', 'some_data' => [3,4,5]],
            ['id' => 3, 'name' => 'user 3', 'some_data' => [5,6,7]],
        ], $collection->fetchAll());
    }

    public function testDelete()
    {
        $microDb = new MicroDb(__DIR__ . '/storage', 'test-db');
        $collection = $microDb->getCollection('users');
        $this->assertCount(0, $collection->fetchAll());
        $collection->insertBatch([
            ['name' => 'user 1', 'some_data' => [1,2,3]],
            ['name' => 'user 2', 'some_data' => [3,4,5]],
            ['name' => 'user 3', 'some_data' => [5,6,7]],
        ]);
        $collection->delete(function ($item) {
            return $item['id'] === 2;
        });
        $this->assertEquals([
            ['id' => 1, 'name' => 'user 1', 'some_data' => [1,2,3]],
            ['id' => 3, 'name' => 'user 3', 'some_data' => [5,6,7]],
        ], $collection->fetchAll());
    }

    public function testFetchOne()
    {
        $microDb = new MicroDb(__DIR__ . '/storage', 'test-db');
        $collection = $microDb->getCollection('users');
        $this->assertCount(0, $collection->fetchAll());
        $collection->insertBatch([
            ['name' => 'user 1', 'some_data' => [1,2,3]],
            ['name' => 'user 2', 'some_data' => [3,4,5]],
            ['name' => 'user 3', 'some_data' => [5,6,7]],
        ]);
        $item = $collection->fetchOne(function ($item) {
            return $item['id'] === 2;
        });
        $this->assertEquals(['id' => 2, 'name' => 'user 2', 'some_data' => [3,4,5]], $item);
    }

    public function testFetchAll()
    {
        $microDb = new MicroDb(__DIR__ . '/storage', 'test-db');
        $collection = $microDb->getCollection('users');
        $this->assertCount(0, $collection->fetchAll());
        $collection->insertBatch([
            ['name' => 'user 1', 'some_data' => [1,2,3]],
            ['name' => 'user 2', 'some_data' => [3,4,5]],
            ['name' => 'user 3', 'some_data' => [5,6,7]],
        ]);
        $items = $collection->fetchAll(function ($item) {
            return \in_array(5, $item['some_data'], true);
        });
        $this->assertEquals([
            ['id' => 2, 'name' => 'user 2', 'some_data' => [3,4,5]],
            ['id' => 3, 'name' => 'user 3', 'some_data' => [5,6,7]],
        ], $items);
    }

    public function testFetchAllAndSort()
    {
        $microDb = new MicroDb(__DIR__ . '/storage', 'test-db');
        $collection = $microDb->getCollection('users');
        $this->assertCount(0, $collection->fetchAll());
        $collection->insertBatch([
            ['name' => 'user 1', 'some_data' => [1,2,3]],
            ['name' => 'user 2', 'some_data' => [3,4,5]],
            ['name' => 'user 3', 'some_data' => [5,6,7]],
        ]);
        $items = $collection->fetchAll(function ($item) {
            return \in_array(5, $item['some_data'], true);
        }, function ($item1, $item2) {
            return ($item1['name'] <=> $item2['name']) * -1;
        });
        $this->assertEquals([
            ['id' => 3, 'name' => 'user 3', 'some_data' => [5,6,7]],
            ['id' => 2, 'name' => 'user 2', 'some_data' => [3,4,5]],
        ], $items);
    }

    public function testFetchAllAndLimitOffset()
    {
        $microDb = new MicroDb(__DIR__ . '/storage', 'test-db');
        $collection = $microDb->getCollection('users');
        $this->assertCount(0, $collection->fetchAll());
        $collection->insertBatch([
            ['name' => 'user 1', 'some_data' => [1,2,3]],
            ['name' => 'user 2', 'some_data' => [3,4,5]],
            ['name' => 'user 3', 'some_data' => [5,6,7]],
        ]);
        $items = $collection->fetchAll(function ($item) {
            return \in_array(5, $item['some_data'], true);
        }, null, 1, 1);
        $this->assertEquals([
            ['id' => 3, 'name' => 'user 3', 'some_data' => [5,6,7]],
        ], $items);
    }

    public function testDrop()
    {
        $microDb = new MicroDb(__DIR__ . '/storage', 'test-db');
        $collection = $microDb->getCollection('users');
        $collection->insertBatch([
            ['name' => 'user 1', 'some_data' => [1,2,3]],
            ['name' => 'user 2', 'some_data' => [3,4,5]],
            ['name' => 'user 3', 'some_data' => [5,6,7]],
        ]);
        $collection->drop();
        $this->assertFileNotExists(__DIR__ . '/storage/test-db_users.fdb');
    }

    public function testRuntime()
    {
        $microDb = new MicroDb(__DIR__ . '/storage', 'test-db', 'runtime');
        $collection = $microDb->getCollection('users');
        $this->assertCount(0, $collection->fetchAll());
        $collection->insertBatch([
            ['name' => 'user 1', 'some_data' => [1,2,3]],
            ['name' => 'user 2', 'some_data' => [3,4,5]],
            ['name' => 'user 3', 'some_data' => [5,6,7]],
        ]);
        $this->assertFileNotExists(__DIR__ . '/storage/test-db_users.fdb');
        $items = $collection->fetchAll(function ($item) {
            return \in_array(5, $item['some_data'], true);
        });
        $this->assertEquals([
            ['id' => 2, 'name' => 'user 2', 'some_data' => [3,4,5]],
            ['id' => 3, 'name' => 'user 3', 'some_data' => [5,6,7]],
        ], $items);

        $this->assertEquals([
            __DIR__ . '/storage/test-db_users.fdb' => [
                'items' => [
                    ['id' => 1, 'name' => 'user 1', 'some_data' => [1,2,3]],
                    ['id' => 2, 'name' => 'user 2', 'some_data' => [3,4,5]],
                    ['id' => 3, 'name' => 'user 3', 'some_data' => [5,6,7]],
                ],
                'inc' => 4,
            ],
        ], StorageRuntime::dump());

        $collection->drop();
        $this->assertEquals([], StorageRuntime::dump());

        $collection->insert(['name' => 'user 1', 'some_data' => [1,2,3]]);
        StorageRuntime::clear();
        $this->assertEquals([], StorageRuntime::dump());
    }

    protected function clearDir()
    {
        @\unlink(__DIR__ . '/storage/test-db_users.fdb');
        @\rmdir(__DIR__ . '/storage');
    }
}
