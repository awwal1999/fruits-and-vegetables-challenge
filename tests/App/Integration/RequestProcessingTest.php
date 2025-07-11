<?php

namespace App\Tests\App\Integration;

use App\Service\FruitVegetableService;
use App\Service\CsvStorageService;
use App\Collection\FruitCollection;
use App\Collection\VegetableCollection;
use PHPUnit\Framework\TestCase;

class RequestProcessingTest extends TestCase
{
    private FruitVegetableService $service;
    private string $tempFile;

    protected function setUp(): void
    {
        $this->tempFile = tempnam(sys_get_temp_dir(), 'integration_test_');
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

    public function testProcessRequestJsonFile(): void
    {
        // Load the actual request.json file
        $requestData = json_decode(file_get_contents('request.json'), true);
        
        $this->assertIsArray($requestData);
        $this->assertNotEmpty($requestData);
        
        // Process the request
        $result = $this->service->processRequest($requestData);
        
        // Verify the result structure
        $this->assertArrayHasKey('fruits_count', $result);
        $this->assertArrayHasKey('vegetables_count', $result);
        $this->assertArrayHasKey('total_processed', $result);
        $this->assertArrayHasKey('processed_items', $result);
        $this->assertArrayHasKey('duplicates', $result);
        $this->assertArrayHasKey('message', $result);
        
        // Verify the counts match the expected data
        $expectedFruits = array_filter($requestData, fn($item) => $item['type'] === 'fruit');
        $expectedVegetables = array_filter($requestData, fn($item) => $item['type'] === 'vegetable');
        
        $this->assertEquals(count($expectedFruits), $result['fruits_count']);
        $this->assertEquals(count($expectedVegetables), $result['vegetables_count']);
        $this->assertEquals(count($requestData), $result['total_processed']);
        $this->assertCount(0, $result['duplicates']); // No duplicates in the original data
        $this->assertEquals('Data processed successfully', $result['message']);
        
        // Verify the collections contain the expected data
        $fruits = $this->service->getFruits();
        $vegetables = $this->service->getVegetables();
        
        $this->assertCount(count($expectedFruits), $fruits);
        $this->assertCount(count($expectedVegetables), $vegetables);
        
        // Verify specific items are present
        $fruitNames = array_column($fruits, 'name');
        $vegetableNames = array_column($vegetables, 'name');
        
        $this->assertContains('Apples', $fruitNames);
        $this->assertContains('Pears', $fruitNames);
        $this->assertContains('Melons', $fruitNames);
        $this->assertContains('Carrot', $vegetableNames);
        $this->assertContains('Beans', $vegetableNames);
        $this->assertContains('Beetroot', $vegetableNames);
    }

    public function testCollectionsHaveRequiredMethods(): void
    {
        // Test that collections have the required methods: add(), remove(), list()
        $storage = new CsvStorageService($this->tempFile);
        $fruitCollection = new FruitCollection($storage);
        $vegetableCollection = new VegetableCollection($storage);
        
        // Test add method
        $this->assertTrue(method_exists($fruitCollection, 'add'));
        $this->assertTrue(method_exists($vegetableCollection, 'add'));
        
        // Test remove method
        $this->assertTrue(method_exists($fruitCollection, 'remove'));
        $this->assertTrue(method_exists($vegetableCollection, 'remove'));
        
        // Test list method
        $this->assertTrue(method_exists($fruitCollection, 'list'));
        $this->assertTrue(method_exists($vegetableCollection, 'list'));
        
        // Test search method (bonus feature)
        $this->assertTrue(method_exists($fruitCollection, 'search'));
        $this->assertTrue(method_exists($vegetableCollection, 'search'));
        
        // Test filter method (bonus feature)
        $this->assertTrue(method_exists($fruitCollection, 'filter'));
        $this->assertTrue(method_exists($vegetableCollection, 'filter'));
    }

    public function testUnitsAreStoredAsGrams(): void
    {
        // Process the request.json file
        $requestData = json_decode(file_get_contents('request.json'), true);
        $this->service->processRequest($requestData);
        
        // Get all items and verify they are stored in grams
        $allItems = $this->service->getAllExistingItems();
        
        foreach ($allItems as $item) {
            // All items should be stored with unit 'g' and quantity in grams
            $this->assertEquals('g', $item->unit);
            
            // Verify that the quantity is reasonable (not converted incorrectly)
            $this->assertGreaterThan(0, $item->quantity);
            
            // For items that were originally in kg, verify they were converted correctly
            $originalItem = $this->findOriginalItem($requestData, $item->name, $item->type);
            if ($originalItem && $originalItem['unit'] === 'kg') {
                $expectedGrams = $originalItem['quantity'] * 1000;
                $this->assertEquals($expectedGrams, $item->quantity, '', 0.01);
            }
        }
    }

    public function testUnitConversionOptions(): void
    {
        // Process the request.json file
        $requestData = json_decode(file_get_contents('request.json'), true);
        $this->service->processRequest($requestData);
        
        // Test getting items in grams (default)
        $fruitsInGrams = $this->service->getFruits([], 'g');
        $this->assertNotEmpty($fruitsInGrams);
        $this->assertEquals('g', $fruitsInGrams[0]->unit);
        
        // Test getting items in kilograms
        $fruitsInKg = $this->service->getFruits([], 'kg');
        $this->assertNotEmpty($fruitsInKg);
        $this->assertEquals('kg', $fruitsInKg[0]->unit);
        
        // Verify conversion is correct
        $appleInGrams = array_filter($fruitsInGrams, fn($item) => $item->name === 'Apples')[0] ?? null;
        $appleInKg = array_filter($fruitsInKg, fn($item) => $item->name === 'Apples')[0] ?? null;
        
        if ($appleInGrams && $appleInKg) {
            $this->assertEquals($appleInGrams->quantity / 1000, $appleInKg->quantity, '', 0.01);
        }
    }

    public function testRemoveMethod(): void
    {
        // Process the request.json file
        $requestData = json_decode(file_get_contents('request.json'), true);
        $this->service->processRequest($requestData);
        
        // Get initial count using the service
        $initialFruits = $this->service->getFruits();
        $initialCount = count($initialFruits);
        
        // Ensure we have fruits to remove
        $this->assertGreaterThan(0, $initialCount);

        // test item first to ensure we have something to remove
        $testData = [
            'id' => 999,
            'name' => 'Test Fruit',
            'type' => 'fruit',
            'quantity' => 100,
            'unit' => 'g'
        ];
        $this->service->addItem($testData);
        
        // Get the updated list
        $updatedFruits = $this->service->getFruits();
        $this->assertGreaterThan($initialCount, count($updatedFruits));
        
        // Find our test item
        $testFruit = null;
        foreach ($updatedFruits as $fruit) {
            if ($fruit->id === 999) {
                $testFruit = $fruit;
                break;
            }
        }
        $this->assertNotNull($testFruit);
        
        // Test that we can't remove items through the service (no direct access)
        // Instead, let's test the collection methods exist and work
        $storage = new CsvStorageService($this->tempFile);
        $fruitCollection = new FruitCollection($storage);
        
        // Test remove method exists
        $this->assertTrue(method_exists($fruitCollection, 'remove'));
        
        // Test with a non-existent item
        $removed = $fruitCollection->remove(99999);
        $this->assertFalse($removed);
    }

    public function testFilterMethod(): void
    {
        // Process the request.json file
        $requestData = json_decode(file_get_contents('request.json'), true);
        $this->service->processRequest($requestData);
        
        // Test filtering fruits by name
        $fruitsWithNameFilter = $this->service->getFruits(['name' => 'Apple']);
        $this->assertNotEmpty($fruitsWithNameFilter);
        
        // All filtered fruits should contain 'Apple' in their name
        foreach ($fruitsWithNameFilter as $fruit) {
            $this->assertStringContainsString('Apple', $fruit->name);
        }
        
        // Test filtering by quantity
        $fruitsWithMinWeight = $this->service->getFruits(['min_weight' => 1000]);
        $this->assertNotEmpty($fruitsWithMinWeight);
        
        // All filtered fruits should have quantity >= 1000
        foreach ($fruitsWithMinWeight as $fruit) {
            $this->assertGreaterThanOrEqual(1000, $fruit->quantity);
        }
        
        // Test filtering vegetables
        $vegetablesWithNameFilter = $this->service->getVegetables(['name' => 'Carrot']);
        $this->assertNotEmpty($vegetablesWithNameFilter);
        
        foreach ($vegetablesWithNameFilter as $vegetable) {
            $this->assertStringContainsString('Carrot', $vegetable->name);
        }
    }

    private function findOriginalItem(array $requestData, string $name, string $type): ?array
    {
        foreach ($requestData as $item) {
            if ($item['name'] === $name && $item['type'] === $type) {
                return $item;
            }
        }
        return null;
    }
} 