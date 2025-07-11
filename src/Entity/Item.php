<?php

namespace App\Entity;

class Item
{
    public function __construct(
        public int $id,
        public string $name,
        public string $type,
        public float $quantity,
        public string $unit
    ) {}

    public const UNIT_KILOGRAM = 'kg';
    public const UNIT_GRAM = 'g';

    public function getQuantityInGrams(): float
    {
        return $this->unit === self::UNIT_KILOGRAM ? $this->quantity * 1000 : $this->quantity;
    }

    public function getQuantityInKilograms(): float
    {
        return $this->unit === self::UNIT_GRAM ? $this->quantity / 1000 : $this->quantity;
    }
} 