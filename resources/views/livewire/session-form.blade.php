<div>
    <x-ui.sheet name="session-form" :title="$sessionId ? 'Editar sessão' : 'Registrar estudo'">
        <form wire:submit="save" class="space-y-5">
            <div>
                <label for="sf-subject" class="mb-1.5 block text-sm font-semibold">O que você estudou?</label>
                <select id="sf-subject" wire:model.live="subjectId" class="block w-full rounded-xl border-2 border-rule bg-sheet px-4 py-3 outline-none focus:border-pen">
                    <option value="">Escolha…</option>
                    @foreach ($courses as $course)
                        @if ($course->subjects->isNotEmpty())
                            <optgroup label="{{ $course->name }}">
                                @foreach ($course->subjects as $item)
                                    <option value="{{ $item->id }}">{{ $item->name }}</option>
                                @endforeach
                            </optgroup>
                        @endif
                    @endforeach
                </select>
                @error('subjectId') <p class="mt-1.5 text-sm text-late">{{ $message }}</p> @enderror
            </div>

            @if ($topics->isNotEmpty())
                <x-ui.select label="Tópico" name="topicId" wire:model.live="topicId" :options="$topics->pluck('title', 'id')->all()" placeholder="Geral (sem tópico)" />
            @endif

            <div class="grid grid-cols-2 gap-3">
                <x-ui.input label="Dia" name="date" type="date" wire:model="date" :max="now()->toDateString()" />
                <x-ui.input label="Começou às" name="time" type="time" wire:model="time" hint="Opcional" />
            </div>

            <div>
                <x-ui.input label="Duração" name="duration" wire:model="duration" placeholder="Ex.: 45 min ou 1h30" />
                <div class="mt-2 flex flex-wrap gap-2">
                    @foreach ([15, 30, 45, 60, 90, 120] as $m)
                        <button type="button" wire:click="setDuration({{ $m }})" class="tap rounded-full bg-sheet-2 px-3 py-1 text-sm font-semibold text-ink-soft hover:text-ink">{{ \App\Support\Duration::human($m * 60) }}</button>
                    @endforeach
                </div>
            </div>

            <div>
                <p class="mb-1.5 text-sm font-semibold">Tipo de estudo</p>
                <div class="flex flex-wrap gap-2">
                    @foreach (\App\Models\StudySession::KINDS as $key => [$label, $icon])
                        <label class="tap flex cursor-pointer items-center gap-1.5 rounded-full border-2 border-rule px-3 py-1.5 text-sm font-semibold has-[:checked]:border-pen has-[:checked]:bg-pen-soft has-[:checked]:text-pen">
                            <input type="radio" value="{{ $key }}" wire:model="kind" class="sr-only"><x-icon :name="$icon" class="size-4" /> {{ $label }}
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="grid grid-cols-3 gap-3">
                <x-ui.input label="Páginas" name="pages" type="number" inputmode="numeric" min="0" wire:model="pages" />
                <x-ui.input label="Questões" name="questionsTotal" type="number" inputmode="numeric" min="0" wire:model="questionsTotal" />
                <x-ui.input label="Acertos" name="questionsCorrect" type="number" inputmode="numeric" min="0" wire:model="questionsCorrect" />
            </div>

            <x-ui.textarea label="Anotações" name="notes" wire:model="notes" rows="2" />

            @if ($topicId)
                <label class="flex cursor-pointer items-center gap-3 rounded-xl bg-sheet-2 px-4 py-3">
                    <input type="checkbox" wire:model="markDone" class="size-5 accent-[#2f855a]">
                    <span class="text-sm font-semibold">Terminei este tópico</span>
                </label>
            @endif

            <div class="flex gap-2">
                @if ($sessionId)
                    <x-ui.button type="button" variant="secondary" icon="trash" wire:click="delete" wire:confirm="Excluir esta sessão?" class="!text-late" aria-label="Excluir sessão" />
                @endif
                <x-ui.button class="flex-1" icon="check">Salvar</x-ui.button>
            </div>
        </form>
    </x-ui.sheet>
</div>
