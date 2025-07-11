<?php

namespace App\Request;

class GetItemsRequest
{
    public ?string $type;
    public ?string $unit;
    public ?string $name;
    public ?float $minQuantity;
    public ?float $maxQuantity;

    public function __construct(array $query)
    {
        $this->type = $query['type'] ?? null;
        $this->unit = $query['unit'] ?? 'g';
        $this->name = $query['name'] ?? null;
        $this->minQuantity = isset($query['min_quantity']) ? (float)$query['min_quantity'] : null;
        $this->maxQuantity = isset($query['max_quantity']) ? (float)$query['max_quantity'] : null;
    }

    public function getFilters(): array
    {
        $filters = [];
        if ($this->name !== null) $filters['name'] = $this->name;
        if ($this->minQuantity !== null) $filters['min_quantity'] = $this->minQuantity;
        if ($this->maxQuantity !== null) $filters['max_quantity'] = $this->maxQuantity;
        return $filters;
    }
} 