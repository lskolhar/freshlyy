@props([
    'active' => false,
])

<button
    type="submit"
    {{ $attributes->merge([
        'class' => (
            $active
                ? 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                : 'bg-green-400 text-white hover:bg-green-600'
        ) . ' rounded-md px-3 py-2 text-sm font-medium'
    ]) }}
>
    {{ $slot }}
</button>
