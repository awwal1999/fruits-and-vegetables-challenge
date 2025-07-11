<?php

namespace App\Service;

use App\Entity\Item;

class CsvStorageService
{
    private string $csvFile;

    public function __construct(string $csvFile)
    {
        $this->csvFile = $csvFile;
        if (!file_exists($this->csvFile) || filesize($this->csvFile) === 0) {
            $handle = fopen($this->csvFile, 'w');
            fputcsv($handle, ['id', 'name', 'type', 'quantity', 'unit']);
            fclose($handle);
        }
    }

    /** @return Item[] */
    public function read(): array 
    {
        if (!file_exists($this->csvFile)) {
            return [];
        }
        $items = [];
        $handle = fopen($this->csvFile, 'r');
        if ($handle === false) {
            return [];
        }

        fgetcsv($handle);
        while (($data = fgetcsv($handle)) !== false) {
            if (count($data) >= 5) {
                $items[] = new Item(
                    (int) $data[0],
                    $data[1],
                    $data[2],
                    (float) $data[3],
                    $data[4]
                );
            }
        }
        fclose($handle);
        return $items;
    }

    public function save(array $newItems): void
    {
        // Read all existing items
        $existingItems = $this->read();
        if (empty($newItems)) {
            // If no new items, just keep existing
            $itemsToSave = $existingItems;
        } else {
            // Determine the type of the new items (assume all are the same type)
            $type = $newItems[0]->type;
            // Remove all items of this type from existing
            $filtered = array_filter($existingItems, fn($item) => $item->type !== $type);
            // Merge filtered existing with new items
            $itemsToSave = array_merge($filtered, $newItems);
        }
        $handle = fopen($this->csvFile, 'w');
        if ($handle === false) {
            throw new \RuntimeException("Cannot open file for writing: {$this->csvFile}");
        }
        // Write header
        fputcsv($handle, ['id', 'name', 'type', 'quantity', 'unit']);
        foreach ($itemsToSave as $item) {
            fputcsv($handle, [
                $item->id,
                $item->name,
                $item->type,
                $item->quantity,
                $item->unit
            ]);
        }
        fclose($handle);
    }

    public function saveAll(array $allItems): void
    {
        $handle = fopen($this->csvFile, 'w');
        if ($handle === false) {
            throw new \RuntimeException("Cannot open file for writing: {$this->csvFile}");
        }
        // Write header
        fputcsv($handle, ['id', 'name', 'type', 'quantity', 'unit']);
        foreach ($allItems as $item) {
            fputcsv($handle, [
                $item->id,
                $item->name,
                $item->type,
                $item->quantity,
                $item->unit
            ]);
        }
        fclose($handle);
    }
} 