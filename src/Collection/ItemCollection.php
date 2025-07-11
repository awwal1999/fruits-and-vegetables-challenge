<?php

namespace App\Collection;

use App\Entity\Item;
use App\Service\CsvStorageService;

abstract class ItemCollection
{
    /** @var Item[] */
    protected array $items = [];

    public function __construct(protected CsvStorageService $storage)
    {
        $this->items = $this->loadItems();
    }

    abstract protected function getType(): string;

    protected function loadItems(): array
    {
        return array_filter(
            $this->storage->read(),
            fn(Item $item) => $item->type === $this->getType()
        );
    }

    public function add(Item $item): void 
    {
        $this->items[] = $item;
    }

    public function remove(int $id): bool 
    {
        foreach ($this->items as $key => $item) {
            if ($item->id === $id) {
                unset($this->items[$key]);
                $this->items = array_values($this->items);
                return true;
            }
        }
        return false;
    }

    public function list(?string $unit = Item::UNIT_GRAM): array 
    {
        return array_map(function (Item $item) use ($unit) {
            $convertedItem = clone $item;
            if ($unit === Item::UNIT_KILOGRAM) {
                $convertedItem->quantity = $item->getQuantityInKilograms();
                $convertedItem->unit = Item::UNIT_KILOGRAM;
            } else {
                $convertedItem->quantity = $item->getQuantityInGrams();
                $convertedItem->unit = Item::UNIT_GRAM;
            }
            return $convertedItem;
        }, $this->items);
    }

    public function search(string $term): array 
    {
        return array_filter($this->items, function (Item $item) use ($term) {
            return stripos($item->name, $term) !== false;
        });
    }

    public function filter(callable $fn): array 
    {
        return array_filter($this->items, $fn);
    }

    public function append(array $newItems): void
    {
        foreach ($newItems as $item) {
            $this->items[] = $item;
        }
        
        $this->save();
    }

    public function save(): void
    {
        $this->storage->save($this->items);
    }

    public function getStorage(): CsvStorageService
    {
        return $this->storage;
    }
}