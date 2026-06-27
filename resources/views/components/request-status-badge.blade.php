@props(['status'])

@php
    $map = [
        'draft'     => ['bg-gray-100 text-gray-600', __('Draft')],
        'open'      => ['bg-green-100 text-green-700', __('Open')],
        'matched'   => ['bg-blue-100 text-blue-700', __('Matched')],
        'completed' => ['bg-indigo-100 text-indigo-700', __('Completed')],
        'closed'    => ['bg-gray-100 text-gray-500', __('Closed')],
        'expired'   => ['bg-red-100 text-red-600', __('Expired')],
    ];
    [$classes, $label] = $map[$status] ?? ['bg-gray-100 text-gray-600', $status];
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium $classes"]) }}>
    {{ $label }}
</span>
