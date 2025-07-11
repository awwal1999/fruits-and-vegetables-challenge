<?php

namespace App\Service;

use App\Collection\FruitCollection;
use App\Collection\VegetableCollection;
use App\Entity\Item;

class FruitVegetableService
{

    public function __construct(
        private FruitCollection $fruitCollection,
        private VegetableCollection $vegetableCollection
    ) {}

    public function getAllExistingItems(): array
    {
        return array_merge(
            $this->fruitCollection->list(),
            $this->vegetableCollection->list()
        );
    }

    public function addItem(array $data): array
    {
        $existing = $this->getAllExistingItems();
        $key = strtolower($data['name']) . '_' . $data['type'];

        foreach ($existing as $item) {
            if ($item->id === $data['id']) {
                return ['status' => 'duplicate', 'reason' => 'Duplicate id'];
            }
            if (strtolower($item->name) . '_' . $item->type === $key) {
                return ['status' => 'duplicate', 'reason' => 'Duplicate name and type'];
            }
        }

        $item = new Item(
            $data['id'],
            $data['name'],
            $data['type'],
            $data['quantity'],
            $data['unit']
        );

        $this->getCollection($item->type)->add($item);
        $this->getCollection($item->type)->save();

        return ['status' => 'success', 'item' => $item];
    }

    public function getFruits(array $filters = [], string $unit = 'g'): array
    {
        $fruits = $this->fruitCollection->list($unit);
        
        if (!empty($filters)) {
            $fruits = $this->fruitCollection->filter(function (Item $item) use ($filters) {
                if (isset($filters['name']) && stripos($item->name, $filters['name']) === false) {
                    return false;
                }
                if (isset($filters['min_weight']) && $item->quantity < $filters['min_weight']) {
                    return false;
                }
                if (isset($filters['max_weight']) && $item->quantity > $filters['max_weight']) {
                    return false;
                }
                return true;
            });
        }
        
        return $fruits;
    }

    public function getVegetables(array $filters = [], string $unit = 'g'): array
    {
        $vegetables = $this->vegetableCollection->list($unit);
        
        if (!empty($filters)) {
            $vegetables = $this->vegetableCollection->filter(function (Item $item) use ($filters) {
                if (isset($filters['name']) && stripos($item->name, $filters['name']) === false) {
                    return false;
                }
                if (isset($filters['min_weight']) && $item->quantity < $filters['min_weight']) {
                    return false;
                }
                if (isset($filters['max_weight']) && $item->quantity > $filters['max_weight']) {
                    return false;
                }
                return true;
            });
        }
        
        return $vegetables;
    }

    /**
     * Query the collections with optional filters and unit.
     * @param string|null $type 'fruit', 'vegetable', or null for both
     * @param array $filters Optional filters: name, min_quantity, max_quantity
     * @param string $unit Unit for returned quantities (default 'g')
     * @return array
     */
    public function getItems(?string $type = null, array $filters = [], string $unit = 'g'): array
    {
        $result = [];
        if ($type === 'fruit' || $type === null) {
            $result['fruits'] = $this->getFruits($filters, $unit);
        }
        if ($type === 'vegetable' || $type === null) {
            $result['vegetables'] = $this->getVegetables($filters, $unit);
        }
        return $result;
    }

    public function processRequest(array $dataList): array
    {
        $existing = $this->getAllExistingItems();
        $existingIds = array_column($existing, 'id');
        $existingKeys = array_map(
            fn($item) => strtolower($item->name) . '_' . $item->type,
            $existing
        );

        $newItems = [];
        $duplicates = [];
        $processedItems = [];

        foreach ($dataList as $data) {
            $idDuplicate = in_array($data['id'], $existingIds, true);
            $key = strtolower($data['name']) . '_' . $data['type'];
            $nameTypeDuplicate = in_array($key, $existingKeys, true);

            if ($idDuplicate || $nameTypeDuplicate) {
                $reasons = [];
                if ($idDuplicate) $reasons[] = 'Duplicate id';
                if ($nameTypeDuplicate) $reasons[] = 'Duplicate name and type';

                $duplicates[] = [
                    'item' => $data,
                    'reason' => implode(' & ', $reasons)
                ];
                continue;
            }

            $item = new Item(
                $data['id'],
                $data['name'],
                $data['type'],
                $data['quantity'],
                $data['unit']
            );

            $newItems[] = $item;
            $processedItems[] = [
                'item' => $data,
                'status' => 'stored'
            ];
        }

        $fruits = array_filter($newItems, fn(Item $item) => $item->type === 'fruit');
        $vegetables = array_filter($newItems, fn(Item $item) => $item->type === 'vegetable');

        $this->fruitCollection->append($fruits);
        $this->vegetableCollection->append($vegetables);
        // Save all items at once to avoid overwriting
        $allItems = array_merge($this->fruitCollection->list(), $this->vegetableCollection->list());
        $this->fruitCollection->getStorage()->saveAll($allItems);

        return [
            'fruits_count' => count($fruits),
            'vegetables_count' => count($vegetables),
            'total_processed' => count($processedItems),
            'processed_items' => $processedItems,
            'duplicates' => $duplicates,
            'message' => 'Data processed successfully'
        ];
    }

    private function getCollection(string $type): FruitCollection|VegetableCollection
    {
        return $type === 'fruit' ? $this->fruitCollection : $this->vegetableCollection;
    }
}