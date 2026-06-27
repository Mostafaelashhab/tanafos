{{-- Admin section sub-navigation --}}
<div class="flex gap-1 overflow-x-auto no-scrollbar mb-6 -mx-1 px-1">
    @foreach ([
        ['admin.dashboard', 'grid', __('Overview')],
        ['admin.merchants', 'storefront', __('Merchants')],
        ['admin.users', 'users', __('Users')],
        ['admin.requests', 'document', __('Requests')],
        ['admin.plans', 'credit-card', __('Plans')],
    ] as [$route, $icon, $label])
        <a href="{{ route($route) }}" wire:navigate
           @class([
               'flex items-center gap-2 px-4 py-2 rounded-full text-sm font-medium whitespace-nowrap',
               'bg-brand-600 text-white' => request()->routeIs($route),
               'bg-white ring-1 ring-gray-200 text-gray-600 hover:bg-gray-50' => ! request()->routeIs($route),
           ])>
            <x-icon :name="$icon" class="w-4 h-4" />
            {{ $label }}
        </a>
    @endforeach
</div>
