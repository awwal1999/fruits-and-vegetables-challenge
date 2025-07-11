<?php

namespace App\Tests\App\Collection;

use App\Collection\ItemCollection;
use App\Entity\Item;
use App\Service\CsvStorageService;
use PHPUnit\Framework\TestCase;

class TestItemCollection extends ItemCollection
{
    protected function getType(): string
    {
        return 'test';
    }
}

class ItemCollectionTest extends TestCase
{
    private TestItemCollection $collection;
    private string $tempFile;

    protected function setUp(): void
    {
        $this->tempFile = tempnam(sys_get_temp_dir(), 'csvtest_');
        $storage = new CsvStorageService($this->tempFile);
        $this->collection = new TestItemCollection($storage);
    }

    protected function tearDown(): void
    {
        if (file_exists($this->tempFile)) {
            unlink($this->tempFile);
        }
    }

    public function testAddItem(): void
    {
        $item = new Item(1, 'Test Item', 'test', 100, 'g');
        $this->collection->add($item);

        $items = $this->collection->list();
        $this->assertCount(1, $items);
        $this->assertEquals('Test Item', $items[0]->name);
    }

    public function testRemoveItem(): void
    {
        $item1 = new Item(1, 'Test Item 1', 'test', 100, 'g');
        $item2 = new Item(2, 'Test Item 2', 'test', 200, 'g');

        $this->collection->add($item1);
        $this->collection->add($item2);

        // Remove item with ID 1
        $result = $this->collection->remove(1);
        $this->assertTrue($result);

        $items = $this->collection->list();
        $this->assertCount(1, $items);
        $this->assertEquals('Test Item 2', $items[0]->name);

        // Try to remove non-existent item
        $result = $this->collection->remove(999);
        $this->assertFalse($result);
    }

    public function testListWithGrams(): void
    {
        $item = new Item(1, 'Test Item', 'test', 100, 'g');
        $this->collection->add($item);

        $items = $this->collection->list('g');
        $this->assertCount(1, $items);
        $this->assertEquals(100, $items[0]->quantity);
        $this->assertEquals('g', $items[0]->unit);
    }

    public function testListWithKilograms(): void
    {
        $item = new Item(1, 'Test Item', 'test', 1500, 'g');
        $this->collection->add($item);

        $items = $this->collection->list('kg');
        $this->assertCount(1, $items);
        $this->assertEquals(1.5, $items[0]->quantity, '', 0.01);
        $this->assertEquals('kg', $items[0]->unit);
    }

    public function testListWithMixedUnits(): void
    {
        $item1 = new Item(1, 'Test Item 1', 'test', 1000, 'g');
        $item2 = new Item(2, 'Test Item 2', 'test', 2, 'kg');

        $this->collection->add($item1);
        $this->collection->add($item2);

        // List in grams
        $items = $this->collection->list('g');
        $this->assertCount(2, $items);
        $this->assertEquals(1000, $items[0]->quantity);
        $this->assertEquals(2000, $items[1]->quantity);

        // List in kilograms
        $items = $this->collection->list('kg');
        $this->assertCount(2, $items);
        $this->assertEquals(1.0, $items[0]->quantity, '', 0.01);
        $this->assertEquals(2.0, $items[1]->quantity, '', 0.01);
    }

    public function testSearch(): void
    {
        $item1 = new Item(1, 'Apple', 'test', 100, 'g');
        $item2 = new Item(2, 'Banana', 'test', 200, 'g');
        $item3 = new Item(3, 'Orange', 'test', 300, 'g');

        $this->collection->add($item1);
        $this->collection->add($item2);
        $this->collection->add($item3);

        // Search for items containing 'an' (matches Banana and Orange)
        $results = $this->collection->search('an');
        $this->assertCount(2, $results);
        
        // Convert to indexed array for easier access
        $results = array_values($results);
        $this->assertEquals('Banana', $results[0]->name);
        $this->assertEquals('Orange', $results[1]->name);

        // Search for items containing 'orange'
        $results = $this->collection->search('orange');
        $this->assertCount(1, $results);
        
        // Convert to indexed array for easier access
        $results = array_values($results);
        $this->assertEquals('Orange', $results[0]->name);

        // Search for non-existent item
        $results = $this->collection->search('xyz');
        $this->assertCount(0, $results);
    }

    public function testSearchCaseInsensitive(): void
    {
        $item = new Item(1, 'Apple', 'test', 100, 'g');
        $this->collection->add($item);

        $results = $this->collection->search('apple');
        $this->assertCount(1, $results);
        $this->assertEquals('Apple', $results[0]->name);

        $results = $this->collection->search('APPLE');
        $this->assertCount(1, $results);
        $this->assertEquals('Apple', $results[0]->name);
    }

    public function testFilter(): void
    {
        $item1 = new Item(1, 'Apple', 'test', 100, 'g');
        $item2 = new Item(2, 'Banana', 'test', 200, 'g');
        $item3 = new Item(3, 'Orange', 'test', 300, 'g');

        $this->collection->add($item1);
        $this->collection->add($item2);
        $this->collection->add($item3);

        // Filter by quantity greater than 150
        $results = $this->collection->filter(function (Item $item) {
            return $item->quantity > 150;
        });
        $this->assertCount(2, $results);
        
        // Convert to indexed array for easier access
        $results = array_values($results);
        $this->assertEquals('Banana', $results[0]->name);
        $this->assertEquals('Orange', $results[1]->name);

        // Filter by name starting with 'A'
        $results = $this->collection->filter(function (Item $item) {
            return strpos($item->name, 'A') === 0;
        });
        $this->assertCount(1, $results);
        
        // Convert to indexed array for easier access
        $results = array_values($results);
        $this->assertEquals('Apple', $results[0]->name);
    }

    public function testAppend(): void
    {
        $item1 = new Item(1, 'Apple', 'test', 100, 'g');
        $item2 = new Item(2, 'Banana', 'test', 200, 'g');

        $this->collection->append([$item1, $item2]);

        $items = $this->collection->list();
        $this->assertCount(2, $items);
        $this->assertEquals('Apple', $items[0]->name);
        $this->assertEquals('Banana', $items[1]->name);
    }

    public function testAppendEmptyArray(): void
    {
        $this->collection->append([]);

        $items = $this->collection->list();
        $this->assertCount(0, $items);
    }

    public function testSave(): void
    {
        // Use FruitCollection for a real type
        $storage = new \App\Service\CsvStorageService($this->tempFile);
        $fruitCollection = new \App\Collection\FruitCollection($storage);
        $item = new \App\Entity\Item(1, 'Test Fruit', 'fruit', 100, 'g');
        $fruitCollection->add($item);
        $fruitCollection->save();

        // Create a new collection instance to verify persistence
        $newCollection = new \App\Collection\FruitCollection($storage);
        $items = $newCollection->list();
        $this->assertNotEmpty($items);
        $this->assertEquals('Test Fruit', $items[0]->name);
        $this->assertEquals('fruit', $items[0]->type);
    }

    public function testLoadItems(): void
    {
        // Use FruitCollection for a real type
        $storage = new \App\Service\CsvStorageService($this->tempFile);
        $fruitCollection = new \App\Collection\FruitCollection($storage);
        $item1 = new \App\Entity\Item(1, 'Apple', 'fruit', 100, 'g');
        $item2 = new \App\Entity\Item(2, 'Banana', 'fruit', 200, 'g');
        $item3 = new \App\Entity\Item(3, 'Carrot', 'vegetable', 300, 'g'); // Different type
        $fruitCollection->add($item1);
        $fruitCollection->add($item2);
        // Do not add $item3 to fruitCollection
        $fruitCollection->save();

        // Create a new collection instance
        $newCollection = new \App\Collection\FruitCollection($storage);

        // Should only load items with type 'fruit'
        $items = $newCollection->list();
        $this->assertCount(2, $items);
        $this->assertEquals('Apple', $items[0]->name);
        $this->assertEquals('Banana', $items[1]->name);
    }
} 