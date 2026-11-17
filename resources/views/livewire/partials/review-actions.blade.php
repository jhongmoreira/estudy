<x-ui.review-row :review="$r" :steps="$steps">
    <div class="flex shrink-0 items-center gap-1" x-data="{ menu: false }">
        <a href="{{ route('study', ['topico' => $r->topic_id, 'tipo' => 'review']) }}" wire:navigate class="tap grid size-9 place-items-center rounded-xl bg-pen text-white dark:text-[#14161c]" aria-label="Revisar com o cronômetro" title="Revisar com o cronômetro">
            <x-icon name="play" class="size-4" />
        </a>
        <button type="button" wire:click="rate({{ $r->id }})" class="tap grid size-9 place-items-center rounded-xl border border-rule" aria-label="Marcar como feita" title="Marcar como feita">
            <x-icon name="check" class="size-4" />
        </button>
        <div class="relative">
            <button type="button" @click="menu = !menu" class="tap grid size-9 place-items-center rounded-xl text-ink-soft hover:bg-sheet-2" aria-label="Mais opções"><x-icon name="more" class="size-4" /></button>
            <div x-show="menu" x-cloak @click.outside="menu = false" class="absolute right-0 z-20 mt-1 w-44 overflow-hidden rounded-xl border border-rule bg-sheet shadow-lg">
                <button type="button" wire:click="skip({{ $r->id }})" @click="menu = false" class="block w-full px-4 py-2.5 text-left text-sm hover:bg-sheet-2">Pular para o próximo dia</button>
                <button type="button" wire:click="openMove({{ $r->id }})" @click="menu = false" class="block w-full px-4 py-2.5 text-left text-sm hover:bg-sheet-2">Escolher data…</button>
            </div>
        </div>
    </div>
</x-ui.review-row>
