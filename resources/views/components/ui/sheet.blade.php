@props(['name', 'title' => null])
{{--
    Folha que sobe de baixo (celular) / modal (desktop).
    Abrir: $dispatch('open-sheet', 'nome') · Livewire: $this->dispatch('open-sheet', 'nome')
    Fechar: $dispatch('close-sheet', 'nome') · arrastar para baixo · Esc
--}}
<div x-data="{
        open: false, dragY: 0, startY: null,
        close() { this.open = false; this.dragY = 0 },
        start(e) { this.startY = e.touches[0].clientY },
        move(e) { if (this.startY !== null) this.dragY = Math.max(0, e.touches[0].clientY - this.startY) },
        end() { if (this.dragY > 100) this.close(); this.dragY = 0; this.startY = null },
     }"
     x-on:open-sheet.window="if ($event.detail === @js($name) || $event.detail?.[0] === @js($name)) open = true"
     x-on:close-sheet.window="if ($event.detail === @js($name) || $event.detail?.[0] === @js($name)) close()"
     x-on:keydown.escape.window="close()"
     x-effect="document.body.style.overflow = open ? 'hidden' : ''"
     class="relative z-50" x-cloak>
    <div x-show="open" x-transition.opacity.duration.200ms @click="close()" class="fixed inset-0 bg-[#1f2a44]/40 backdrop-blur-[2px]"></div>

    <div x-show="open"
         x-transition:enter="transition duration-250 ease-out" x-transition:enter-start="translate-y-full lg:translate-y-4 lg:opacity-0" x-transition:enter-end="translate-y-0 lg:opacity-100"
         x-transition:leave="transition duration-200 ease-in" x-transition:leave-start="translate-y-0 lg:opacity-100" x-transition:leave-end="translate-y-full lg:translate-y-4 lg:opacity-0"
         :style="dragY ? `transform: translateY(${dragY}px)` : ''"
         role="dialog" aria-modal="true" @if ($title) aria-label="{{ $title }}" @endif
         class="fixed inset-x-0 bottom-0 max-h-[90dvh] overflow-y-auto rounded-t-[28px] border-t border-rule bg-sheet pb-safe shadow-2xl lg:inset-x-auto lg:bottom-auto lg:left-1/2 lg:top-1/2 lg:w-full lg:max-w-md lg:-translate-x-1/2 lg:-translate-y-1/2 lg:rounded-[28px] lg:border">
        <div class="sticky top-0 z-10 bg-sheet px-5 pt-3" @touchstart="start" @touchmove="move" @touchend="end">
            <div class="mx-auto mb-2 h-1.5 w-10 rounded-full bg-rule lg:hidden"></div>
            @if ($title)
                <div class="flex items-center justify-between border-b-2 border-double border-rule pb-3">
                    <h2 class="text-lg font-bold">{{ $title }}</h2>
                    <button type="button" @click="close()" class="tap -mr-2 rounded-full p-2 text-ink-soft hover:bg-sheet-2" aria-label="Fechar">
                        <x-icon name="x" />
                    </button>
                </div>
            @endif
        </div>
        <div class="px-5 pb-6 pt-4">
            {{ $slot }}
        </div>
    </div>
</div>
