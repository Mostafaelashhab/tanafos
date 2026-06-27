<?php

use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app')] class extends Component {
    use WithPagination;

    #[Url]
    public string $status = 'pending';

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function approve(int $id, PaymentService $payments): void
    {
        $payments->approve(Payment::findOrFail($id), Auth::user());
        session()->flash('status', __('Payment approved and applied.'));
    }

    public function reject(int $id, PaymentService $payments): void
    {
        $payments->reject(Payment::findOrFail($id), Auth::user());
        session()->flash('status', __('Payment rejected.'));
    }

    #[Computed]
    public function payments()
    {
        return Payment::query()
            ->with('merchantProfile')
            ->when($this->status !== 'all', fn ($q) => $q->where('status', $this->status))
            ->latest()
            ->paginate(20);
    }
}; ?>

<div class="py-8">
    <div class="max-w-6xl mx-auto px-4">
        <h1 class="font-bold text-2xl text-gray-900 mb-6">{{ __('Admin') }}</h1>
        @include('admin._nav')

        @if (session('status'))
            <div class="mb-4 rounded-xl bg-green-50 p-4 text-sm text-green-700">{{ session('status') }}</div>
        @endif

        <div class="flex flex-wrap gap-2 mb-4">
            @foreach (['pending' => __('Pending'), 'approved' => __('Approved'), 'rejected' => __('Rejected'), 'all' => __('All')] as $key => $label)
                <button wire:click="$set('status', '{{ $key }}')"
                        @class([
                            'px-3 py-1.5 rounded-full text-sm border',
                            'bg-brand-600 text-white border-brand-600' => $status === $key,
                            'bg-white text-gray-600 border-gray-200' => $status !== $key,
                        ])>{{ $label }}</button>
            @endforeach
        </div>

        <div class="bg-white rounded-2xl shadow-soft divide-y divide-gray-50 overflow-hidden">
            @forelse ($this->payments as $p)
                <div class="px-5 py-4 flex flex-wrap items-center justify-between gap-3">
                    <div class="min-w-0">
                        <div class="font-medium text-gray-900">
                            {{ $p->merchantProfile?->business_name }}
                            <span class="text-gray-400 text-sm">· {{ $p->itemLabel() }}</span>
                        </div>
                        <div class="text-sm text-gray-500 flex flex-wrap items-center gap-x-3 gap-y-1 mt-0.5">
                            <span class="font-bold text-gray-700">{{ $p->amount }} {{ __('EGP') }}</span>
                            <span>{{ $p->methodLabel() }}</span>
                            <span dir="ltr">{{ $p->sender_number }}</span>
                            @if ($p->reference)<span>#{{ $p->reference }}</span>@endif
                            @if ($p->proof_path)
                                <a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($p->proof_path) }}" target="_blank"
                                   class="text-brand-600 inline-flex items-center gap-1"><x-icon name="eye" class="w-4 h-4" /> {{ __('Proof') }}</a>
                            @endif
                            <span class="text-xs text-gray-400">{{ $p->created_at->diffForHumans() }}</span>
                        </div>
                    </div>

                    @if ($p->isPending())
                        <div class="flex items-center gap-2 shrink-0">
                            <button wire:click="approve({{ $p->id }})" wire:confirm="{{ __('Approve & apply this purchase?') }}"
                                    class="px-3 py-1.5 rounded-lg text-sm font-semibold bg-emerald-600 text-white hover:bg-emerald-700">{{ __('Approve') }}</button>
                            <button wire:click="reject({{ $p->id }})" wire:confirm="{{ __('Reject this payment?') }}"
                                    class="px-3 py-1.5 rounded-lg text-sm font-semibold bg-gray-100 text-gray-600 hover:bg-gray-200">{{ __('Reject') }}</button>
                        </div>
                    @else
                        <span @class([
                            'shrink-0 px-2.5 py-0.5 rounded-full text-xs font-medium',
                            'bg-emerald-100 text-emerald-700' => $p->status === 'approved',
                            'bg-rose-100 text-rose-600' => $p->status === 'rejected',
                        ])>{{ $p->status === 'approved' ? __('Approved') : __('Rejected') }}</span>
                    @endif
                </div>
            @empty
                <div class="px-5 py-12 text-center text-gray-400">{{ __('No payments here.') }}</div>
            @endforelse
        </div>

        <div class="mt-4">{{ $this->payments->links() }}</div>
    </div>
</div>
