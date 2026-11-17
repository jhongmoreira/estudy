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

    <x-ui.sheet name="delete-account" title="Excluir conta">
        <form wire:submit="deleteAccount" class="space-y-4">
            <p class="text-sm text-ink-soft">Esta ação é <strong class="text-ink">permanente</strong>: cursos, sessões, revisões e configurações serão apagados. Digite sua senha para confirmar.</p>
            <x-ui.input label="Senha" name="delete_password" type="password" wire:model="delete_password" autocomplete="current-password" />
            <x-ui.button variant="danger" class="w-full">Excluir minha conta</x-ui.button>
        </form>
    </x-ui.sheet>
</div>
