<?php

namespace App\Livewire\Forms;

use Livewire\Form;

class OfferForm extends Form
{
    public ?int $price = null;
    public string $warranty = '';
    public ?int $delivery_days = null;
    public string $description = '';
    public bool $negotiation_enabled = true;

    public function rules(): array
    {
        return [
            'price' => ['required', 'integer', 'min:1'],
            'warranty' => ['nullable', 'string', 'max:255'],
            'delivery_days' => ['nullable', 'integer', 'min:0', 'max:3650'],
            'description' => ['nullable', 'string', 'max:2000'],
            'negotiation_enabled' => ['boolean'],
        ];
    }

    /** @return array{price:int, warranty:?string, delivery_days:?int, description:?string, negotiation_enabled:bool} */
    public function payload(): array
    {
        return [
            'price' => $this->price,
            'warranty' => $this->warranty ?: null,
            'delivery_days' => $this->delivery_days,
            'description' => $this->description ?: null,
            'negotiation_enabled' => $this->negotiation_enabled,
        ];
    }
}
