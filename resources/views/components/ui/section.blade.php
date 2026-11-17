@props(['title', 'action' => null, 'actionHref' => null])
<section {{ $attributes->merge(['class' => 'space-y-3']) }}>
    <div class="flex items-end justify-between gap-3">
        <h2 class="text-lg font-bold"><span class="highlight">{{ $title }}</span></h2>
        @if ($action && $actionHref)
            <a href="{{ $actionHref }}" wire:navigate class="text-sm font-semibold text-pen">{{ $action }}</a>
        @endif
    </div>
    {{ $slot }}
</section>
