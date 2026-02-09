@props([
    'href' => '#',
    'variant' => 'default', // default | green | outline
    'active' => false,
])

@php
    $base = 'px-4 py-2 rounded-md text-sm font-medium transition';

    $variants = [
        'default' => 'bg-gray-100 text-gray-700 hover:bg-gray-200',
        'green'   => 'bg-green-500 text-white hover:bg-green-600',
        'outline' => 'border border-gray-300 text-gray-700 hover:bg-gray-100',
    ];

    $activeClass = $active ? 'ring-2 ring-green-400' : '';
@endphp

<a href="{{ $href }}"
   {{ $attributes->merge([
       'class' => $base . ' ' . ($variants[$variant] ?? $variants['default']) . ' ' . $activeClass
   ]) }}>
    {{ $slot }}
</a>
