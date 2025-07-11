<?php

namespace App\Tests\App\Service;

use App\Service\CsvStorageService;
use App\Entity\Item;
use PHPUnit\Framework\TestCase;

class CsvStorageServiceTest extends TestCase
{
    private string $tempFile;
    private CsvStorageService $storage;

    protected function setUp(): void
    {
        $this->tempFile = tempnam(sys_get_temp_dir(), 'csvtest_');
        $this->storage = new CsvStorageService($this->tempFile);
    }

    protected function tearDown(): void
    {
        if (file_exists($this->tempFile)) {
            unlink($this->tempFile);
        }
    }

    public function testSaveAndReadItems(): void
    {
        $items = [
            new Item(1, 'Apple', 'fruit', 150, 'g'),
            new Item(2, 'Banana', 'fruit', 120, 'g'),
            new Item(3, 'Carrot', 'vegetable', 80, 'g'),
        ];

        $this->storage->save($items);
        $loadedItems = $this->storage->read();

        $this->assertCount(3, $loadedItems);
        $this->assertEquals('Apple', $loadedItems[0]->name);
        $this->assertEquals('Banana', $loadedItems[1]->name);
        $this->assertEquals('Carrot', $loadedItems[2]->name);
    }

    public function testSaveAndReadWithKilograms(): void
    {
        $items = [
            new Item(1, 'Apple', 'fruit', 1.5, 'kg'),
            new Item(2, 'Banana', 'fruit', 2.0, 'kg'),
        ];

        $this->storage->save($items);
        $loadedItems = $this->storage->read();

        $this->assertCount(2, $loadedItems);
        $this->assertEquals(1500, $loadedItems[0]->getQuantityInGrams());
        $this->assertEquals(2000, $loadedItems[1]->getQuantityInGrams());
    }

    public function testReadEmptyFile(): void
    {
        $items = $this->storage->read();
        $this->assertEmpty($items);
    }

    public function testSaveEmptyArray(): void
    {
        $this->storage->save([]);
        $items = $this->storage->read();
        $this->assertEmpty($items);
    }

    public function testSaveOverwritesExistingData(): void
    {
        // Save initial items
        $initialItems = [
            new Item(1, 'Apple', 'fruit', 150, 'g'),
            new Item(2, 'Banana', 'fruit', 120, 'g'),
        ];
        $this->storage->save($initialItems);

        // Save new items (should overwrite)
        $newItems = [
            new Item(3, 'Orange', 'fruit', 200, 'g'),
        ];
        $this->storage->save($newItems);

        $loadedItems = $this->storage->read();
        $this->assertCount(1, $loadedItems);
        $this->assertEquals('Orange', $loadedItems[0]->name);
    }

    public function testSaveWithMixedUnits(): void
    {
        $items = [
            new Item(1, 'Apple', 'fruit', 150, 'g'),
            new Item(2, 'Banana', 'fruit', 1.5, 'kg'),
            new Item(3, 'Carrot', 'vegetable', 800, 'g'),
        ];

        $this->storage->save($items);
        $loadedItems = $this->storage->read();

        $this->assertCount(3, $loadedItems);
        
        // Verify all quantities are stored in grams
        $this->assertEquals(150, $loadedItems[0]->getQuantityInGrams());
        $this->assertEquals(1500, $loadedItems[1]->getQuantityInGrams());
        $this->assertEquals(800, $loadedItems[2]->getQuantityInGrams());
    }

    public function testSaveWithSpecialCharacters(): void
    {
        $items = [
            new Item(1, 'Apple, Red', 'fruit', 150, 'g'),
            new Item(2, 'Banana "Yellow"', 'fruit', 120, 'g'),
            new Item(3, 'Carrot\nOrange', 'vegetable', 80, 'g'),
        ];

        $this->storage->save($items);
        $loadedItems = $this->storage->read();

        $this->assertCount(3, $loadedItems);
        $this->assertEquals('Apple, Red', $loadedItems[0]->name);
        $this->assertEquals('Banana "Yellow"', $loadedItems[1]->name);
        $this->assertEquals('Carrot\nOrange', $loadedItems[2]->name);
    }

    public function testSaveWithLargeQuantities(): void
    {
        $items = [
            new Item(1, 'Large Apple', 'fruit', 999999.99, 'g'),
            new Item(2, 'Huge Banana', 'fruit', 1234567.89, 'kg'),
        ];

        $this->storage->save($items);
        $loadedItems = $this->storage->read();

        $this->assertCount(2, $loadedItems);
        $this->assertEquals(999999.99, $loadedItems[0]->getQuantityInGrams());
        $this->assertEquals(1234567890, $loadedItems[1]->getQuantityInGrams());
    }

    public function testSaveWithZeroQuantity(): void
    {
        $items = [
            new Item(1, 'Empty Apple', 'fruit', 0, 'g'),
        ];

        $this->storage->save($items);
        $loadedItems = $this->storage->read();

        $this->assertCount(1, $loadedItems);
        $this->assertEquals(0, $loadedItems[0]->getQuantityInGrams());
    }

    public function testSaveWithDecimalQuantities(): void
    {
        $items = [
            new Item(1, 'Small Apple', 'fruit', 0.5, 'g'),
            new Item(2, 'Tiny Banana', 'fruit', 0.001, 'kg'),
        ];

        $this->storage->save($items);
        $loadedItems = $this->storage->read();

        $this->assertCount(2, $loadedItems);
        $this->assertEquals(0.5, $loadedItems[0]->getQuantityInGrams());
        $this->assertEquals(1, $loadedItems[1]->getQuantityInGrams());
    }

    public function testFileCreation(): void
    {
        $items = [
            new Item(1, 'Test Item', 'test', 100, 'g'),
        ];

        $this->storage->save($items);
        
        $this->assertFileExists($this->tempFile);
        $this->assertGreaterThan(0, filesize($this->tempFile));
    }

    public function testCsvFormat(): void
    {
        $items = [
            new Item(1, 'Apple', 'fruit', 150, 'g'),
            new Item(2, 'Banana', 'fruit', 120, 'g'),
        ];

        $this->storage->save($items);
        
        $csvContent = file_get_contents($this->tempFile);
        $lines = explode("\n", trim($csvContent));
        
        // Should have header + 2 data rows
        $this->assertCount(3, $lines);
        
        // Check header
        $this->assertEquals('id,name,type,quantity,unit', $lines[0]);
        
        // Check data rows
        $this->assertStringContainsString('1,Apple,fruit,150,g', $lines[1]);
        $this->assertStringContainsString('2,Banana,fruit,120,g', $lines[2]);
    }
} 