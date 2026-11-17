<div>
    <x-ui.page-header title="Perfil" :back="route('more')" />

    <div class="max-w-2xl space-y-6">
        <div class="flex flex-col items-center gap-2 py-2">
            <x-ui.avatar :user="$user" class="size-20 text-2xl" />
            <p class="text-sm text-ink-soft">Conta criada em {{ $user->created_at?->translatedFormat('j \d\e F \d\e Y') }}</p>
        </div>

        <x-ui.section title="Seus dados">
            <x-ui.card class="p-4">
                <form wire:submit="saveProfile" class="space-y-4">
                    <x-ui.input label="Nome" name="name" wire:model="name" autocomplete="name" />
                    <x-ui.input label="E-mail" name="email" type="email" wire:model="email" autocomplete="email" inputmode="email" />
                    <x-ui.button class="w-full">Salvar</x-ui.button>
                </form>
            </x-ui.card>
        </x-ui.section>

        <x-ui.section title="Seus dados">
            <x-ui.card class="divide-y divide-rule" x-data>
                <x-ui.list-item icon="download" label="Exportar backup completo" description="Arquivo JSON com cursos, sessões, revisões e agenda" :href="route('export.backup')" :navigate="false" />
                <x-ui.list-item icon="download" label="Exportar sessões (planilha)" description="CSV para Excel ou Google Planilhas" :href="route('export.sessions')" :navigate="false" />
                <x-ui.list-item icon="review" label="Importar backup" description="Restaura um arquivo exportado pelo Estudy" @click="$dispatch('open-sheet', 'import')" :chevron="true" />
                <x-ui.list-item icon="trash" tone="marker" label="Recomeçar do zero" description="Apaga cursos e estudos; mantém sua conta" @click="$dispatch('open-sheet', 'reset-data')" :chevron="true" />
            </x-ui.card>
        </x-ui.section>

        <x-ui.section title="Segurança">
            <x-ui.card class="divide-y divide-rule" x-data>
                <x-ui.list-item icon="lock" label="Alterar senha" @click="$dispatch('open-sheet', 'password')" :chevron="true" />
                <x-ui.list-item icon="trash" tone="late" label="Excluir conta" description="Apaga todos os seus dados" @click="$dispatch('open-sheet', 'delete-account')" :chevron="true" />
            </x-ui.card>
        </x-ui.section>
    </div>

    <x-ui.sheet name="password" title="Alterar senha">
        <form wire:submit="updatePassword" class="space-y-4">
            <x-ui.input label="Senha atual" name="current_password" type="password" wire:model="current_password" autocomplete="current-password" />
            <x-ui.input label="Nova senha" name="password" type="password" wire:model="password" autocomplete="new-password" hint="Mínimo de 8 caracteres." />
            <x-ui.input label="Confirmar nova senha" name="password_confirmation" type="password" wire:model="password_confirmation" autocomplete="new-password" />
            <x-ui.button class="w-full">Alterar senha</x-ui.button>
        </form>
    </x-ui.sheet>

    <x-ui.sheet name="import" title="Importar backup">
        <form wire:submit="importBackup" class="space-y-4">
            <p class="text-sm text-ink-soft">Os dados do arquivo <strong class="text-ink">substituem</strong> todos os seus cursos, sessões, revisões e agenda atuais.</p>
            <label class="flex cursor-pointer flex-col items-center gap-2 rounded-2xl border-2 border-dashed border-rule px-4 py-6 text-center hover:border-pen">
                <x-icon name="download" class="size-7 rotate-180 text-pen" />
                <span class="font-semibold">{{ $backup ? $backup->getClientOriginalName() : 'Escolher arquivo .json' }}</span>
                <input type="file" wire:model="backup" accept="application/json,.json" class="sr-only">
            </label>
            <div wire:loading wire:target="backup" class="text-sm text-ink-soft">Lendo arquivo…</div>
            @error('backup') <p class="text-sm text-late">{{ $message }}</p> @enderror
            @if ($backupSummary !== [])
                <div class="rounded-2xl bg-sheet-2 p-3 text-sm">
                    <p class="mb-1 font-semibold">O arquivo contém:</p>
                    <p class="text-ink-soft">@foreach ($backupSummary as $k => $v){{ $v }} {{ $k }}@if (! $loop->last) · @endif @endforeach</p>
                </div>
                <x-ui.input label="Sua senha, para confirmar" name="import_password" type="password" wire:model="import_password" autocomplete="current-password" />
                <x-ui.button class="w-full" icon="check">Substituir pelos dados do arquivo</x-ui.button>
            @endif
        </form>
    </x-ui.sheet>

    <x-ui.sheet name="reset-data" title="Recomeçar do zero">
        <form wire:submit="resetData" class="space-y-4">
            <p class="text-sm text-ink-soft">Serão apagados <strong class="text-ink">cursos, disciplinas, tópicos, sessões, revisões, agenda, ciclo e certificados</strong>. Sua conta, metas e preferências continuam.</p>
            <p class="text-sm text-ink-soft">Se quiser guardar uma cópia, exporte o backup antes. Digite sua senha para confirmar.</p>
            <x-ui.input label="Senha" name="reset_password" type="password" wire:model="reset_password" autocomplete="current-password" />
            <x-ui.button variant="marker" class="w-full" icon="trash">Apagar tudo e recomeçar</x-ui.button>
        </form>
    </x-ui.sheet>

    <x-ui.sheet name="delete-account" title="Excluir conta">
        <form wire:submit="deleteAccount" class="space-y-4">
            <p class="text-sm text-ink-soft">Esta ação é <strong class="text-ink">permanente</strong>: cursos, sessões, revisões e configurações serão apagados. Digite sua senha para confirmar.</p>
            <x-ui.input label="Senha" name="delete_password" type="password" wire:model="delete_password" autocomplete="current-password" />
            <x-ui.button variant="danger" class="w-full">Excluir minha conta</x-ui.button>
        </form>
    </x-ui.sheet>
</div>
