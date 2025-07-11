<?php

namespace App\Collection;

class FruitCollection extends ItemCollection
{
    protected string $type = 'fruit';

    protected function getType(): string
    {
        return $this->type;
    }
} 