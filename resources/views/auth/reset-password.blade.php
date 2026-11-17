<x-layouts.guest title="Nova senha" heading="Crie uma nova senha">
    <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">
        <x-ui.input label="E-mail" name="email" type="email" :value="old('email', $request->email)" autocomplete="email" required />
        <x-ui.input label="Nova senha" name="password" type="password" autocomplete="new-password" hint="Mínimo de 8 caracteres." required autofocus />
        <x-ui.input label="Confirmar nova senha" name="password_confirmation" type="password" autocomplete="new-password" required />
        <x-ui.button class="w-full">Salvar nova senha</x-ui.button>
    </form>
</x-layouts.guest>
