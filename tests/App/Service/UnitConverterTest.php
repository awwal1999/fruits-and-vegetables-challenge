<?php

namespace App\Tests\App\Service;

use App\Entity\Item;
use PHPUnit\Framework\TestCase;

class UnitConverterTest extends TestCase
{
    public function testConvertKilogramsToGrams(): void
    {
        // arrange
        $item = new Item(1, 'Apple', 'fruit', 1.5, 'kg');
        // assert
        $this->assertEquals(1500, $item->getQuantityInGrams());
    }

    public function testConvertGramsToGrams(): void
    {
        // arrange
        $item = new Item(1, 'Apple', 'fruit', 500, 'g');
        // assert
        $this->assertEquals(500, $item->getQuantityInGrams());
    }

    public function testConvertGramsToKilograms(): void
    {
        // arrange
        $item = new Item(1, 'Apple', 'fruit', 1500, 'g');
        // assert
        $this->assertEquals(1.5, $item->getQuantityInKilograms());
    }

    public function testConvertKilogramsToKilograms(): void
    {
        // arrange
        $item = new Item(1, 'Apple', 'fruit', 2.5, 'kg');
        // assert
        $this->assertEquals(2.5, $item->getQuantityInKilograms());
    }

    public function testConvertZeroValues(): void
    {
        // arrange
        $item = new Item(1, 'Empty Apple', 'fruit', 0, 'kg');
        // assert
        $this->assertEquals(0, $item->getQuantityInGrams());

        // arrange
        $item = new Item(1, 'Empty Apple', 'fruit', 0, 'g');
        // assert
        $this->assertEquals(0, $item->getQuantityInKilograms());
    }

    public function testConvertDecimalValues(): void
    {
        // arrange
        $item = new Item(1, 'Small Apple', 'fruit', 0.5, 'kg');
        // assert
        $this->assertEquals(500, $item->getQuantityInGrams());

        // arrange
        $item = new Item(1, 'Small Apple', 'fruit', 0.001, 'kg');
        // assert
        $this->assertEquals(1, $item->getQuantityInGrams());

        // arrange
        $item = new Item(1, 'Small Apple', 'fruit', 500, 'g');
        // assert
        $this->assertEquals(0.5, $item->getQuantityInKilograms());
    }

    public function testConvertLargeValues(): void
    {
        // arrange
        $item = new Item(1, 'Large Apple', 'fruit', 1000, 'kg');
        // assert
        $this->assertEquals(1000000, $item->getQuantityInGrams());

        // arrange
        $item = new Item(1, 'Large Apple', 'fruit', 1000000, 'g');
        // assert
        $this->assertEquals(1000, $item->getQuantityInKilograms());
    }

    public function testConvertPrecision(): void
    {
        // arrange
        $item = new Item(1, 'Precise Apple', 'fruit', 1.234567, 'kg');
        // assert
        $this->assertEquals(1234.567, $item->getQuantityInGrams(), '', 0.001);

        // arrange
        $item = new Item(1, 'Precise Apple', 'fruit', 1234.567, 'g');
        // assert
        $this->assertEquals(1.234567, $item->getQuantityInKilograms(), '', 0.000001);
    }

    public function testUnitConstants(): void
    {
        // assert
        $this->assertEquals('kg', Item::UNIT_KILOGRAM);
        $this->assertEquals('g', Item::UNIT_GRAM);
    }

    public function testItemProperties(): void
    {
        // arrange
        $item = new Item(1, 'Apple', 'fruit', 150, 'g');
        // assert
        $this->assertEquals(1, $item->id);
        $this->assertEquals('Apple', $item->name);
        $this->assertEquals('fruit', $item->type);
        $this->assertEquals(150, $item->quantity);
        $this->assertEquals('g', $item->unit);
    }

    public function testItemWithKilograms(): void
    {
        // arrange
        $item = new Item(1, 'Apple', 'fruit', 1.5, 'kg');
        // assert
        $this->assertEquals(1, $item->id);
        $this->assertEquals('Apple', $item->name);
        $this->assertEquals('fruit', $item->type);
        $this->assertEquals(1.5, $item->quantity);
        $this->assertEquals('kg', $item->unit);
    }
}