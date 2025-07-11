<?php

namespace App\Collection;

class VegetableCollection extends ItemCollection
{
    protected string $type = 'vegetable';

    protected function getType(): string
    {
        return $this->type;
    }
} 