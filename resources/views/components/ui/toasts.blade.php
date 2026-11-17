{{-- $store.toast.show('Salvo!') · Livewire: $this->dispatch('toast', message: '...') · sessão: session()->flash('toast', '...') --}}
<div x-data
     @if (session('toast')) x-init="$nextTick(() => $store.toast.show(@js(session('toast'))))" @endif
     class="pointer-events-none fixed inset-x-0 bottom-24 z-[60] flex flex-col items-center gap-2 px-4 lg:bottom-8" aria-live="polite">
    <template x-for="toast in $store.toast.items" :key="toast.id">
        <div x-transition.opacity @click="$store.toast.dismiss(toast.id)"
             class="pointer-events-auto flex w-full max-w-sm items-center gap-3 rounded-2xl bg-ink px-4 py-3 text-sm text-paper shadow-xl">
            <span class="grid size-6 shrink-0 place-items-center rounded-full"
                  :class="{ 'bg-marker text-[#1f2a44]': toast.type === 'success', 'bg-late text-white': toast.type === 'error', 'bg-pen text-white': toast.type === 'info' }">
                <x-icon name="check" class="size-4" x-show="toast.type === 'success'" />
                <x-icon name="alert" class="size-4" x-show="toast.type === 'error'" />
                <x-icon name="info" class="size-4" x-show="toast.type === 'info'" />
            </span>
            <span x-text="toast.message"></span>
        </div>
    </template>
</div>
