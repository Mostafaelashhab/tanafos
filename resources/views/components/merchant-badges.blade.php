@props(['profile'])

@php
    // [icon, label, classes]
    $catalog = [
        'verified'       => ['badge-check',  __('Verified seller'),  'bg-blue-100 text-blue-700'],
        'top_merchant'   => ['star',         __('Top merchant'),     'bg-amber-100 text-amber-700'],
        'fast_responder' => ['bolt',         __('Fast responder'),   'bg-emerald-100 text-emerald-700'],
        'rising_star'    => ['trending-up',  __('Rising star'),      'bg-violet-100 text-violet-700'],
    ];
    $level = $profile->level();
    $levelLabel = app()->getLocale() === 'ar' ? $level['name_ar'] : $level['name'];
    $levelColors = [
        'bronze' => 'bg-orange-100 text-orange-800',
        'silver' => 'bg-gray-200 text-gray-700',
        'gold' => 'bg-amber-100 text-amber-800',
        'diamond' => 'bg-cyan-100 text-cyan-800',
        'elite' => 'bg-indigo-100 text-indigo-800',
    ];
@endphp

<div {{ $attributes->merge(['class' => 'flex flex-wrap items-center gap-2']) }}>
    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $levelColors[$level['key']] ?? 'bg-gray-100 text-gray-700' }}">
        <x-icon name="trophy" class="w-3.5 h-3.5" /> {{ $levelLabel }}
    </span>
    @foreach ($profile->badges() as $badge)
        @php([$icon, $label, $classes] = $catalog[$badge])
        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium {{ $classes }}">
            <x-icon :name="$icon" class="w-3.5 h-3.5" /> {{ $label }}
        </span>
    @endforeach
</div>
