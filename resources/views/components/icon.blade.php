<svg
    {{ $attributes->merge(['class' => 'inline-block']) }}
    width="{{ $size }}"
    height="{{ $size }}"
>
    <use xlink:href="#icon-{{ $name }}"></use>
</svg>