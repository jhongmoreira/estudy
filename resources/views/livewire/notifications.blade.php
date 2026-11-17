<div>
    <x-ui.page-header title="Notificações" :back="route('more')" subtitle="Lembretes de estudo, revisões e prazos" />

    <div class="max-w-2xl space-y-6">
        {{-- Este aparelho --}}
        <div x-data="pushDevice(@js($publicKey), @js(route('push.subscribe')), @js(route('push.unsubscribe')))">
            <x-ui.card class="p-4">
                <div class="flex items-start gap-3">
                    <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-marker/40 text-marker-ink"><x-icon name="bell" /></span>
                    <div class="min-w-0 flex-1">
                        <p class="font-semibold">Neste aparelho</p>
                        <p class="text-sm text-ink-soft" x-show="subscribed">Ativadas. Os avisos chegam mesmo com o app fechado.</p>
                        <p class="text-sm text-ink-soft" x-show="! subscribed && supported && ! needsInstall">Receba o lembrete de estudar, as revisões do dia e os prazos.</p>
                        <p class="text-sm text-ink-soft" x-show="! supported && ! needsInstall" x-cloak>Este navegador não suporta notificações push.</p>
                    </div>
                </div>
                <div x-show="needsInstall" x-cloak class="mt-3 rounded-xl bg-pen-soft p-3 text-sm text-pen">
                    No iPhone, as notificações só funcionam com o Estudy <strong>instalado na tela de início</strong> (iOS 16.4+).
                    <button type="button" class="font-semibold underline" x-on:click="$store.install.prompt()">Ver como instalar</button>
                </div>
                <div class="mt-4 grid gap-2" x-show="supported && ! needsInstall" x-cloak>
                    <x-ui.button type="button" x-show="! subscribed" x-on:click="enable()" x-bind:disabled="busy" icon="bell">Ativar notificações</x-ui.button>
                    <template x-if="subscribed">
                        <div class="grid grid-cols-2 gap-2">
                            <x-ui.button type="button" variant="secondary" wire:click="sendTest">Enviar teste</x-ui.button>
                            <x-ui.button type="button" variant="secondary" x-on:click="disable()" x-bind:disabled="busy">Desativar</x-ui.button>
                        </div>
                    </template>
                    <p class="text-xs text-ink-soft" x-show="permission === 'denied'">As notificações estão bloqueadas no navegador. Libere nas configurações do site.</p>
                </div>
            </x-ui.card>
        </div>

        <x-ui.section title="O que avisar">
            <x-ui.card class="divide-y divide-rule">
                @foreach (\App\Models\User::NOTIFICATION_TYPES as $type => [$label, $description])
                    <div class="px-4 py-3" wire:key="pref-{{ $type }}">
                        <label class="flex cursor-pointer items-center gap-3">
                            <span class="min-w-0 flex-1">
                                <span class="block font-semibold">{{ $label }}</span>
                                <span class="block text-sm text-ink-soft">{{ $description }}</span>
                            </span>
                            <input type="checkbox" wire:model.live="prefs.{{ $type }}" class="peer sr-only">
                            <span class="relative h-7 w-12 shrink-0 rounded-full bg-rule transition peer-checked:bg-pen after:absolute after:left-1 after:top-1 after:size-5 after:rounded-full after:bg-white after:transition peer-checked:after:translate-x-5"></span>
                        </label>
                        @if ($type === 'daily' && ($prefs['daily'] ?? true))
                            <div class="mt-3 flex items-center justify-between gap-3 text-sm">
                                <span class="text-ink-soft">Horário do lembrete</span>
                                <input type="time" wire:model.live.debounce.600ms="reminderTime" aria-label="Horário do lembrete" class="rounded-lg border-2 border-rule bg-sheet px-3 py-1.5 outline-none focus:border-pen">
                            </div>
                            @error('reminderTime') <p class="mt-1 text-sm text-late">{{ $message }}</p> @enderror
                        @endif
                    </div>
                @endforeach
            </x-ui.card>
        </x-ui.section>

        @if ($devices->isNotEmpty())
            <x-ui.section title="Aparelhos ativados">
                <x-ui.card class="divide-y divide-rule">
                    @foreach ($devices as $d)
                        <div class="flex items-center gap-3 px-4 py-3" wire:key="dev-{{ $d['id'] }}">
                            <x-icon name="phone" class="text-ink-soft" />
                            <span class="min-w-0 flex-1"><span class="block font-semibold">{{ $d['name'] }}</span><span class="block text-xs text-ink-soft">desde {{ $d['since'] }}</span></span>
                            <button type="button" wire:click="removeDevice({{ $d['id'] }})" class="tap rounded-lg px-2 py-1 text-sm font-semibold text-late hover:bg-sheet-2">Remover</button>
                        </div>
                    @endforeach
                </x-ui.card>
            </x-ui.section>
        @endif

        <x-ui.section title="Recentes">
            @if ($items->isEmpty())
                <p class="rounded-2xl bg-sheet-2 px-4 py-4 text-sm text-ink-soft">Nenhum aviso ainda.</p>
            @else
                <x-ui.card class="divide-y divide-rule">
                    @foreach ($items as $n)
                        <a href="{{ $n->data['url'] ?? '/' }}" wire:navigate class="flex items-start gap-3 px-4 py-3 hover:bg-sheet-2" wire:key="n-{{ $n->id }}">
                            <span class="grid size-9 shrink-0 place-items-center rounded-xl bg-pen-soft text-pen"><x-icon :name="$n->data['icon'] ?? 'bell'" class="size-4" /></span>
                            <span class="min-w-0 flex-1">
                                <span class="block font-semibold">{{ $n->data['title'] ?? '' }}</span>
                                <span class="block text-sm text-ink-soft">{{ $n->data['body'] ?? '' }}</span>
                                <span class="block text-xs text-ink-soft">{{ $n->created_at?->diffForHumans() }}</span>
                            </span>
                        </a>
                    @endforeach
                </x-ui.card>
                <button type="button" wire:click="clearAll" wire:confirm="Apagar todos os avisos da lista?" class="mt-2 text-sm font-semibold text-ink-soft hover:text-ink">Limpar lista</button>
            @endif
        </x-ui.section>
    </div>
</div>
