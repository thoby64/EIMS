@props(['icon' => null, 'name', 'color' => '#64748B'])
@php
    $knownIcons = [
        'asset', 'layers', 'category', 'computer', 'chair', 'building', 'bolt', 'water',
        'vehicle', 'flask', 'education', 'medical', 'shield', 'sports', 'tools', 'kitchen',
        'library', 'leaf', 'license',
    ];
    $iconName = is_string($icon) ? strtolower(trim($icon)) : null;
    $hasIcon = $iconName && in_array($iconName, $knownIcons, true);
    $safeColor = is_string($color) && preg_match('/^#[0-9a-f]{6}$/i', $color) ? $color : '#64748B';
    $words = collect(preg_split('/[\s\-_]+/u', trim($name), -1, PREG_SPLIT_NO_EMPTY))
        ->reject(fn ($word) => strtolower($word) === 'and')
        ->values();
    if ($words->count() >= 2) {
        $initials = \Illuminate\Support\Str::upper(
            \Illuminate\Support\Str::substr($words[0], 0, 1).\Illuminate\Support\Str::substr($words[1], 0, 1)
        );
    } else {
        $word = $words->first() ?: trim($name);
        $initials = \Illuminate\Support\Str::upper(
            \Illuminate\Support\Str::substr($word, 0, 1).\Illuminate\Support\Str::substr($word, -1, 1)
        );
    }
@endphp
<span {{ $attributes->merge(['class' => 'grid size-9 shrink-0 place-items-center']) }} style="color: {{ $safeColor }}" title="{{ $name }}">
    @if($hasIcon)
        <x-eims-icon :name="$iconName" class="size-5" />
    @else
        <span class="text-[11px] font-black tracking-tight">{{ $initials }}</span>
    @endif
</span>
