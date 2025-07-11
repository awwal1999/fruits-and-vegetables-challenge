<?php

namespace App\Tests\App\Service;

use App\Service\FruitVegetableService;
use App\Service\CsvStorageService;
use App\Collection\FruitCollection;
use App\Collection\VegetableCollection;
use App\Entity\Item;
use PHPUnit\Framework\TestCase;

class FruitVegetableServiceTest extends TestCase
{
    private FruitVegetableService $service;
    private string $tempFile;

    protected function setUp(): void
    {
        $this->tempFile = tempnam(sys_get_temp_dir(), 'csvtest_');
        $storage = new CsvStorageService($this->tempFile);
        $fruitCollection = new FruitCollection($storage);
        $vegetableCollection = new VegetableCollection($storage);
        $this->service = new FruitVegetableService($fruitCollection, $vegetableCollection);
    }

    protected function tearDown(): void
    {
        if (file_exists($this->tempFile)) {
            unlink($this->tempFile);
        }
    }

    public function testAddItemSuccess(): void
    {
        $data = [
            'id' => 1,
            'name' => 'Apple',
            'type' => 'fruit',
            'quantity' => 150,
            'unit' => 'g',
        ];
        $result = $this->service->addItem($data);
        $this->assertEquals('success', $result['status']);
        $this->assertInstanceOf(Item::class, $result['item']);
        $this->assertEquals('Apple', $result['item']->name);
        $this->assertEquals('fruit', $result['item']->type);
        $this->assertEquals(150, $result['item']->quantity);
        $this->assertEquals('g', $result['item']->unit);
    }

    public function testAddItemWithKilograms(): void
    {
        $data = [
            'id' => 1,
            'name' => 'Apple',
            'type' => 'fruit',
            'quantity' => 1.5,
            'unit' => 'kg',
        ];
        $result = $this->service->addItem($data);
        $this->assertEquals('success', $result['status']);
        $this->assertEquals(1500, $result['item']->getQuantityInGrams());
    }

    public function testAddItemDuplicateId(): void
    {
        $data1 = [
            'id' => 1,
            'name' => 'Apple',
            'type' => 'fruit',
            'quantity' => 150,
            'unit' => 'g',
        ];
        $data2 = [
            'id' => 1,
            'name' => 'Banana',
            'type' => 'fruit',
            'quantity' => 120,
            'unit' => 'g',
        ];
        $this->service->addItem($data1);
        $result = $this->service->addItem($data2);
        $this->assertEquals('duplicate', $result['status']);
        $this->assertEquals('Duplicate id', $result['reason']);
    }

    public function testAddItemDuplicateNameType(): void
    {
        $data1 = [
            'id' => 1,
            'name' => 'Apple',
            'type' => 'fruit',
            'quantity' => 150,
            'unit' => 'g',
        ];
        $data2 = [
            'id' => 2,
            'name' => 'Apple',
            'type' => 'fruit',
            'quantity' => 200,
            'unit' => 'g',
        ];
        $this->service->addItem($data1);
        $result = $this->service->addItem($data2);
        $this->assertEquals('duplicate', $result['status']);
        $this->assertEquals('Duplicate name and type', $result['reason']);
    }

    public function testAddItemCaseInsensitiveDuplicate(): void
    {
        $data1 = [
            'id' => 1,
            'name' => 'Apple',
            'type' => 'fruit',
            'quantity' => 150,
            'unit' => 'g',
        ];
        $data2 = [
            'id' => 2,
            'name' => 'apple',
            'type' => 'fruit',
            'quantity' => 200,
            'unit' => 'g',
        ];
        $this->service->addItem($data1);
        $result = $this->service->addItem($data2);
        $this->assertEquals('duplicate', $result['status']);
        $this->assertEquals('Duplicate name and type', $result['reason']);
    }

    public function testGetItemsWithFiltersAndUnits(): void
    {
        $this->service->addItem([
            'id' => 1,
            'name' => 'Apple',
            'type' => 'fruit',
            'quantity' => 1500,
            'unit' => 'g',
        ]);
        $this->service->addItem([
            'id' => 2,
            'name' => 'Banana',
            'type' => 'fruit',
            'quantity' => 2,
            'unit' => 'kg',
        ]);
        $this->service->addItem([
            'id' => 3,
            'name' => 'Carrot',
            'type' => 'vegetable',
            'quantity' => 800,
            'unit' => 'g',
        ]);

        // Test filter by name
        $result = $this->service->getItems('fruit', ['name' => 'Apple'], 'g');
        $this->assertCount(1, $result['fruits']);
        $this->assertEquals('Apple', $result['fruits'][0]->name);

        // Test unit conversion to kg
        $result = $this->service->getItems('fruit', [], 'kg');
        $this->assertCount(2, $result['fruits']);
        $this->assertEquals(1.5, $result['fruits'][0]->quantity);
        $this->assertEquals(2.0, $result['fruits'][1]->quantity);

        // Test filter by min quantity
        $result = $this->service->getItems('fruit', ['min_quantity' => 1500], 'g');
        $this->assertCount(2, $result['fruits']);

        // Test filter by max quantity
        $result = $this->service->getItems('fruit', ['max_quantity' => 1600], 'g');
        $this->assertCount(1, $result['fruits']);
    }

    public function testGetAllExistingItems(): void
    {
        $this->service->addItem([
            'id' => 1,
            'name' => 'Apple',
            'type' => 'fruit',
            'quantity' => 150,
            'unit' => 'g',
        ]);
        $this->service->addItem([
            'id' => 2,
            'name' => 'Carrot',
            'type' => 'vegetable',
            'quantity' => 80,
            'unit' => 'g',
        ]);
        $all = $this->service->getAllExistingItems();
        $this->assertCount(2, $all);
        $this->assertEquals('Apple', $all[0]->name);
        $this->assertEquals('Carrot', $all[1]->name);
    }

    public function testGetFruitsOnly(): void
    {
        $this->service->addItem([
            'id' => 1,
            'name' => 'Apple',
            'type' => 'fruit',
            'quantity' => 150,
            'unit' => 'g',
        ]);
        $this->service->addItem([
            'id' => 2,
            'name' => 'Carrot',
            'type' => 'vegetable',
            'quantity' => 80,
            'unit' => 'g',
        ]);

        $fruits = $this->service->getFruits();
        $this->assertCount(1, $fruits);
        $this->assertEquals('Apple', $fruits[0]->name);
    }

    public function testGetVegetablesOnly(): void
    {
        $this->service->addItem([
            'id' => 1,
            'name' => 'Apple',
            'type' => 'fruit',
            'quantity' => 150,
            'unit' => 'g',
        ]);
        $this->service->addItem([
            'id' => 2,
            'name' => 'Carrot',
            'type' => 'vegetable',
            'quantity' => 80,
            'unit' => 'g',
        ]);

        $vegetables = $this->service->getVegetables();
        $this->assertCount(1, $vegetables);
        $this->assertEquals('Carrot', $vegetables[0]->name);
    }

    public function testProcessRequestSuccess(): void
    {
        $dataList = [
            [
                'id' => 1,
                'name' => 'Apple',
                'type' => 'fruit',
                'quantity' => 150,
                'unit' => 'g',
            ],
            [
                'id' => 2,
                'name' => 'Carrot',
                'type' => 'vegetable',
                'quantity' => 80,
                'unit' => 'g',
            ],
        ];

        $result = $this->service->processRequest($dataList);

        $this->assertEquals(1, $result['fruits_count']);
        $this->assertEquals(1, $result['vegetables_count']);
        $this->assertEquals(2, $result['total_processed']);
        $this->assertCount(2, $result['processed_items']);
        $this->assertCount(0, $result['duplicates']);
        $this->assertEquals('Data processed successfully', $result['message']);
    }

    public function testProcessRequestWithDuplicates(): void
    {
        // Add initial items
        $this->service->addItem([
            'id' => 1,
            'name' => 'Apple',
            'type' => 'fruit',
            'quantity' => 150,
            'unit' => 'g',
        ]);

        $dataList = [
            [
                'id' => 1, // Duplicate ID
                'name' => 'Banana',
                'type' => 'fruit',
                'quantity' => 120,
                'unit' => 'g',
            ],
            [
                'id' => 2,
                'name' => 'Apple', // Duplicate name and type
                'type' => 'fruit',
                'quantity' => 200,
                'unit' => 'g',
            ],
            [
                'id' => 3,
                'name' => 'Carrot', // Valid new item
                'type' => 'vegetable',
                'quantity' => 80,
                'unit' => 'g',
            ],
        ];

        $result = $this->service->processRequest($dataList);

        $this->assertEquals(0, $result['fruits_count']);
        $this->assertEquals(1, $result['vegetables_count']);
        $this->assertEquals(1, $result['total_processed']);
        $this->assertCount(1, $result['processed_items']);
        $this->assertCount(2, $result['duplicates']);
    }

    public function testProcessRequestWithMixedUnits(): void
    {
        $dataList = [
            [
                'id' => 1,
                'name' => 'Apple',
                'type' => 'fruit',
                'quantity' => 1.5,
                'unit' => 'kg',
            ],
            [
                'id' => 2,
                'name' => 'Carrot',
                'type' => 'vegetable',
                'quantity' => 800,
                'unit' => 'g',
            ],
        ];

        $result = $this->service->processRequest($dataList);

        $this->assertEquals(1, $result['fruits_count']);
        $this->assertEquals(1, $result['vegetables_count']);
        $this->assertEquals(2, $result['total_processed']);

        // Verify items are stored in grams
        $fruits = $this->service->getFruits();
        $vegetables = $this->service->getVegetables();

        $this->assertEquals(1500, $fruits[0]->getQuantityInGrams());
        $this->assertEquals(800, $vegetables[0]->getQuantityInGrams());
    }

    public function testGetItemsWithTypeFilter(): void
    {
        $this->service->addItem([
            'id' => 1,
            'name' => 'Apple',
            'type' => 'fruit',
            'quantity' => 150,
            'unit' => 'g',
        ]);
        $this->service->addItem([
            'id' => 2,
            'name' => 'Carrot',
            'type' => 'vegetable',
            'quantity' => 80,
            'unit' => 'g',
        ]);

        // Test getting only fruits
        $result = $this->service->getItems('fruit');
        $this->assertArrayHasKey('fruits', $result);
        $this->assertArrayNotHasKey('vegetables', $result);
        $this->assertCount(1, $result['fruits']);

        // Test getting only vegetables
        $result = $this->service->getItems('vegetable');
        $this->assertArrayHasKey('vegetables', $result);
        $this->assertArrayNotHasKey('fruits', $result);
        $this->assertCount(1, $result['vegetables']);

        // Test getting both
        $result = $this->service->getItems();
        $this->assertArrayHasKey('fruits', $result);
        $this->assertArrayHasKey('vegetables', $result);
        $this->assertCount(1, $result['fruits']);
        $this->assertCount(1, $result['vegetables']);
    }

    public function testGetItemsWithComplexFilters(): void
    {
        $this->service->addItem([
            'id' => 1,
            'name' => 'Apple',
            'type' => 'fruit',
            'quantity' => 150,
            'unit' => 'g',
        ]);
        $this->service->addItem([
            'id' => 2,
            'name' => 'Banana',
            'type' => 'fruit',
            'quantity' => 200,
            'unit' => 'g',
        ]);
        $this->service->addItem([
            'id' => 3,
            'name' => 'Carrot',
            'type' => 'vegetable',
            'quantity' => 80,
            'unit' => 'g',
        ]);

        // Test multiple filters
        $filters = [
            'name' => 'a',
            'min_quantity' => 100,
            'max_quantity' => 180,
        ];

        $result = $this->service->getItems('fruit', $filters);
        $this->assertCount(2, $result['fruits']);
        $this->assertEquals('Apple', $result['fruits'][0]->name);
    }
}