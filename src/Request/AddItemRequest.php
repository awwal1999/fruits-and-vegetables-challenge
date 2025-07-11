<?php

namespace App\Request;

use Symfony\Component\Validator\Constraints as Assert;

class AddItemRequest
{
    #[Assert\NotNull(message: 'ID is required')]
    #[Assert\Type('integer', message: 'ID must be an integer')]
    #[Assert\PositiveOrZero(message: 'ID must be zero or positive')]
    public int $id;

    #[Assert\NotBlank(message: 'Name is required')]
    #[Assert\Length(min: 1, max: 255)]
    public string $name;

    #[Assert\NotBlank(message: 'Type is required')]
    #[Assert\Choice(choices: ['fruit', 'vegetable'], message: 'Type must be either "fruit" or "vegetable"')]
    public string $type;

    #[Assert\NotBlank(message: 'Quantity is required')]
    #[Assert\Positive(message: 'Quantity must be positive')]
    public float $quantity;

    #[Assert\NotBlank(message: 'Unit is required')]
    #[Assert\Choice(choices: ['g', 'kg', 'grams', 'kilograms'], message: 'Unit must be g, kg, grams, or kilograms')]
    public string $unit;

    public function __construct(array $data = [])
    {
        $this->id = isset($data['id']) ? (int)$data['id'] : 0;
        $this->name = $data['name'] ?? '';
        $this->type = $data['type'] ?? '';
        $this->quantity = isset($data['quantity']) ? (float)$data['quantity'] : 0;
        $this->unit = $data['unit'] ?? '';
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type,
            'quantity' => $this->quantity,
            'unit' => $this->unit,
        ];
    }
} 