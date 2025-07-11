<?php

namespace App\Tests\App\Request;

use App\Request\GetItemsRequest;
use PHPUnit\Framework\TestCase;

class GetItemsRequestTest extends TestCase
{
    public function testDefaultValues(): void
    {
        $request = new GetItemsRequest([]);
        
        $this->assertNull($request->type);
        $this->assertEquals('g', $request->unit);
        $this->assertNull($request->name);
        $this->assertNull($request->minQuantity);
        $this->assertNull($request->maxQuantity);
    }

    public function testParseAllParameters(): void
    {
        $query = [
            'type' => 'fruit',
            'unit' => 'kg',
            'name' => 'Apple',
            'min_quantity' => '100',
            'max_quantity' => '500',
        ];
        
        $request = new GetItemsRequest($query);
        
        $this->assertEquals('fruit', $request->type);
        $this->assertEquals('kg', $request->unit);
        $this->assertEquals('Apple', $request->name);
        $this->assertEquals(100.0, $request->minQuantity);
        $this->assertEquals(500.0, $request->maxQuantity);
    }

    public function testGetFiltersWithAllFilters(): void
    {
        $query = [
            'name' => 'Apple',
            'min_quantity' => '100',
            'max_quantity' => '500',
        ];
        
        $request = new GetItemsRequest($query);
        $filters = $request->getFilters();
        
        $this->assertArrayHasKey('name', $filters);
        $this->assertArrayHasKey('min_quantity', $filters);
        $this->assertArrayHasKey('max_quantity', $filters);
        $this->assertEquals('Apple', $filters['name']);
        $this->assertEquals(100.0, $filters['min_quantity']);
        $this->assertEquals(500.0, $filters['max_quantity']);
    }

    public function testGetFiltersWithPartialFilters(): void
    {
        $query = [
            'name' => 'Apple',
            'min_quantity' => '100',
        ];
        
        $request = new GetItemsRequest($query);
        $filters = $request->getFilters();
        
        $this->assertArrayHasKey('name', $filters);
        $this->assertArrayHasKey('min_quantity', $filters);
        $this->assertArrayNotHasKey('max_quantity', $filters);
        $this->assertEquals('Apple', $filters['name']);
        $this->assertEquals(100.0, $filters['min_quantity']);
    }

    public function testGetFiltersWithNoFilters(): void
    {
        $request = new GetItemsRequest([]);
        $filters = $request->getFilters();
        
        $this->assertEmpty($filters);
    }

    public function testGetFiltersWithOnlyName(): void
    {
        $query = ['name' => 'Apple'];
        $request = new GetItemsRequest($query);
        $filters = $request->getFilters();
        
        $this->assertArrayHasKey('name', $filters);
        $this->assertArrayNotHasKey('min_quantity', $filters);
        $this->assertArrayNotHasKey('max_quantity', $filters);
        $this->assertEquals('Apple', $filters['name']);
    }

    public function testGetFiltersWithOnlyMinQuantity(): void
    {
        $query = ['min_quantity' => '100'];
        $request = new GetItemsRequest($query);
        $filters = $request->getFilters();
        
        $this->assertArrayNotHasKey('name', $filters);
        $this->assertArrayHasKey('min_quantity', $filters);
        $this->assertArrayNotHasKey('max_quantity', $filters);
        $this->assertEquals(100.0, $filters['min_quantity']);
    }

    public function testGetFiltersWithOnlyMaxQuantity(): void
    {
        $query = ['max_quantity' => '500'];
        $request = new GetItemsRequest($query);
        $filters = $request->getFilters();
        
        $this->assertArrayNotHasKey('name', $filters);
        $this->assertArrayNotHasKey('min_quantity', $filters);
        $this->assertArrayHasKey('max_quantity', $filters);
        $this->assertEquals(500.0, $filters['max_quantity']);
    }

    public function testFloatConversion(): void
    {
        $query = [
            'min_quantity' => '100.5',
            'max_quantity' => '500.75',
        ];
        
        $request = new GetItemsRequest($query);
        
        $this->assertEquals(100.5, $request->minQuantity);
        $this->assertEquals(500.75, $request->maxQuantity);
    }

    public function testTypeValues(): void
    {
        $fruitRequest = new GetItemsRequest(['type' => 'fruit']);
        $this->assertEquals('fruit', $fruitRequest->type);
        
        $vegetableRequest = new GetItemsRequest(['type' => 'vegetable']);
        $this->assertEquals('vegetable', $vegetableRequest->type);
        
        $nullRequest = new GetItemsRequest(['type' => 'invalid']);
        $this->assertEquals('invalid', $nullRequest->type); // No validation in constructor
    }

    public function testUnitValues(): void
    {
        $gramRequest = new GetItemsRequest(['unit' => 'g']);
        $this->assertEquals('g', $gramRequest->unit);
        
        $kgRequest = new GetItemsRequest(['unit' => 'kg']);
        $this->assertEquals('kg', $kgRequest->unit);
        
        $customRequest = new GetItemsRequest(['unit' => 'custom']);
        $this->assertEquals('custom', $customRequest->unit); // No validation in constructor
    }
} 