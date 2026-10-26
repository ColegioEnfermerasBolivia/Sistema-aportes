<svg
    {{ $attributes->merge(['class' => 'inline-block']) }}
    width="{{ $size }}"
    height="{{ $size }}"
>
    <use href="#icon-{{ $name }}"></use>
</svg>