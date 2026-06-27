<?php

namespace App\Livewire\Forms;

use App\Models\Request as DemandRequest;
use App\Models\User;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Validate;
use Livewire\Form;

class RequestForm extends Form
{
    public ?DemandRequest $request = null;

    public string $title = '';
    public ?int $category_id = null;
    public ?int $budget_min = null;
    public ?int $budget_max = null;
    public string $city = '';
    public string $condition = 'any';
    public string $urgency = 'normal';
    public string $payment_method = 'any';
    public bool $warranty_required = false;
    public string $preferred_delivery = '';
    public string $description = '';

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'min:3', 'max:255'],
            'category_id' => ['required', Rule::exists('categories', 'id')->where('is_active', true)],
            'budget_min' => ['nullable', 'integer', 'min:0'],
            'budget_max' => ['nullable', 'integer', 'min:0', 'gte:budget_min'],
            'city' => ['nullable', 'string', 'max:255'],
            'condition' => ['required', Rule::in(DemandRequest::CONDITIONS)],
            'urgency' => ['required', Rule::in(DemandRequest::URGENCIES)],
            'payment_method' => ['required', Rule::in(DemandRequest::PAYMENT_METHODS)],
            'warranty_required' => ['boolean'],
            'preferred_delivery' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
        ];
    }

    /** Hydrate the form from an existing request (edit). */
    public function setRequest(DemandRequest $request): void
    {
        $this->request = $request;

        $this->title = $request->title;
        $this->category_id = $request->category_id;
        $this->budget_min = $request->budget_min;
        $this->budget_max = $request->budget_max;
        $this->city = (string) $request->city;
        $this->condition = $request->condition;
        $this->urgency = $request->urgency;
        $this->payment_method = $request->payment_method;
        $this->warranty_required = $request->warranty_required;
        $this->preferred_delivery = (string) $request->preferred_delivery;
        $this->description = (string) $request->description;
    }

    /** Persist a new request for the given buyer. Optionally publish immediately. */
    public function store(User $buyer, bool $publish = false): DemandRequest
    {
        $this->validate();

        $request = DemandRequest::create([
            ...$this->payload(),
            'buyer_id' => $buyer->id,
            'status' => $publish ? 'open' : 'draft',
            'published_at' => $publish ? now() : null,
        ]);

        $this->request = $request;

        return $request;
    }

    /** Update the bound request. */
    public function update(): DemandRequest
    {
        $this->validate();

        $this->request->update($this->payload());

        return $this->request;
    }

    private function payload(): array
    {
        return [
            'title' => $this->title,
            'category_id' => $this->category_id,
            'budget_min' => $this->budget_min,
            'budget_max' => $this->budget_max,
            'city' => $this->city ?: null,
            'condition' => $this->condition,
            'urgency' => $this->urgency,
            'payment_method' => $this->payment_method,
            'warranty_required' => $this->warranty_required,
            'preferred_delivery' => $this->preferred_delivery ?: null,
            'description' => $this->description ?: null,
        ];
    }
}
