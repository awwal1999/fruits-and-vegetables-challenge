<?php

namespace App\Tests\App\Request;

use App\Request\AddItemRequest;
use PHPUnit\Framework\TestCase;

class AddItemRequestTest extends TestCase
{
    public function testDefaultValues(): void
    {
        $request = new AddItemRequest([]);
        
        $this->assertEquals(0, $request->id);
        $this->assertEquals('', $request->name);
        $this->assertEquals('', $request->type);
        $this->assertEquals(0, $request->quantity);
        $this->assertEquals('', $request->unit);
    }

    public function testParseAllData(): void
    {
        $data = [
            'id' => 1,
            'name' => 'Apple',
            'type' => 'fruit',
            'quantity' => 150,
            'unit' => 'g',
        ];
        
        $request = new AddItemRequest($data);
        
        $this->assertEquals(1, $request->id);
        $this->assertEquals('Apple', $request->name);
        $this->assertEquals('fruit', $request->type);
        $this->assertEquals(150.0, $request->quantity);
        $this->assertEquals('g', $request->unit);
    }

    public function testToArray(): void
    {
        $data = [
            'id' => 1,
            'name' => 'Apple',
            'type' => 'fruit',
            'quantity' => 150,
            'unit' => 'g',
        ];
        
        $request = new AddItemRequest($data);
        $array = $request->toArray();
        
        $this->assertEquals($data, $array);
    }

    public function testToArrayWithFloatQuantity(): void
    {
        $data = [
            'id' => 1,
            'name' => 'Apple',
            'type' => 'fruit',
            'quantity' => 150.5,
            'unit' => 'g',
        ];
        
        $request = new AddItemRequest($data);
        $array = $request->toArray();
        
        $this->assertEquals($data, $array);
    }

    public function testFloatConversion(): void
    {
        $data = [
            'id' => 1,
            'name' => 'Apple',
            'type' => 'fruit',
            'quantity' => '150.5',
            'unit' => 'g',
        ];
        
        $request = new AddItemRequest($data);
        
        $this->assertEquals(1, $request->id);
        $this->assertEquals(150.5, $request->quantity);
    }

    public function testStringQuantityConversion(): void
    {
        $data = [
            'id' => 1,
            'name' => 'Apple',
            'type' => 'fruit',
            'quantity' => '150',
            'unit' => 'g',
        ];
        
        $request = new AddItemRequest($data);
        
        $this->assertEquals(1, $request->id);
        $this->assertEquals(150.0, $request->quantity);
    }

    public function testPartialData(): void
    {
        $data = [
            'id' => 1,
            'name' => 'Apple',
            'type' => 'fruit',
        ];
        
        $request = new AddItemRequest($data);
        
        $this->assertEquals(1, $request->id);
        $this->assertEquals('Apple', $request->name);
        $this->assertEquals('fruit', $request->type);
        $this->assertEquals(0, $request->quantity);
        $this->assertEquals('', $request->unit);
    }

    public function testEmptyData(): void
    {
        $request = new AddItemRequest([]);
        $array = $request->toArray();
        
        $expected = [
            'id' => 0,
            'name' => '',
            'type' => '',
            'quantity' => 0,
            'unit' => '',
        ];
        
        $this->assertEquals($expected, $array);
    }

    public function testWithKilograms(): void
    {
        $data = [
            'id' => 1,
            'name' => 'Apple',
            'type' => 'fruit',
            'quantity' => 1.5,
            'unit' => 'kg',
        ];
        
        $request = new AddItemRequest($data);
        
        $this->assertEquals(1, $request->id);
        $this->assertEquals('Apple', $request->name);
        $this->assertEquals('fruit', $request->type);
        $this->assertEquals(1.5, $request->quantity);
        $this->assertEquals('kg', $request->unit);
    }

    public function testWithVegetableType(): void
    {
        $data = [
            'id' => 2,
            'name' => 'Carrot',
            'type' => 'vegetable',
            'quantity' => 80,
            'unit' => 'g',
        ];
        
        $request = new AddItemRequest($data);
        
        $this->assertEquals(2, $request->id);
        $this->assertEquals('Carrot', $request->name);
        $this->assertEquals('vegetable', $request->type);
        $this->assertEquals(80.0, $request->quantity);
        $this->assertEquals('g', $request->unit);
    }

    public function testWithZeroQuantity(): void
    {
        $data = [
            'id' => 3,
            'name' => 'Empty Apple',
            'type' => 'fruit',
            'quantity' => 0,
            'unit' => 'g',
        ];
        
        $request = new AddItemRequest($data);
        
        $this->assertEquals(3, $request->id);
        $this->assertEquals('Empty Apple', $request->name);
        $this->assertEquals('fruit', $request->type);
        $this->assertEquals(0, $request->quantity);
        $this->assertEquals('g', $request->unit);
    }

    public function testWithNegativeQuantity(): void
    {
        $data = [
            'id' => 4,
            'name' => 'Negative Apple',
            'type' => 'fruit',
            'quantity' => -10,
            'unit' => 'g',
        ];
        
        $request = new AddItemRequest($data);
        
        $this->assertEquals(4, $request->id);
        $this->assertEquals('Negative Apple', $request->name);
        $this->assertEquals('fruit', $request->type);
        $this->assertEquals(-10.0, $request->quantity);
        $this->assertEquals('g', $request->unit);
    }

    public function testWithLargeQuantity(): void
    {
        $data = [
            'id' => 5,
            'name' => 'Large Apple',
            'type' => 'fruit',
            'quantity' => 999999.99,
            'unit' => 'g',
        ];
        
        $request = new AddItemRequest($data);
        
        $this->assertEquals(5, $request->id);
        $this->assertEquals('Large Apple', $request->name);
        $this->assertEquals('fruit', $request->type);
        $this->assertEquals(999999.99, $request->quantity);
        $this->assertEquals('g', $request->unit);
    }
} 