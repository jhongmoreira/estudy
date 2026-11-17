<x-layouts.guest title="Entrar" heading="Bem-vindo de volta">
    <x-slot:subtitle>Entre para continuar de onde parou.</x-slot:subtitle>

    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf
        <x-ui.input label="E-mail" name="email" type="email" :value="old('email')" autocomplete="email" inputmode="email" required autofocus />
        <x-ui.input label="Senha" name="password" type="password" autocomplete="current-password" required />

        <div class="flex items-center justify-between text-sm">
            <label class="flex items-center gap-2">
                <input type="checkbox" name="remember" value="1" checked class="size-4 rounded accent-[#3047c9]">
                Manter conectado
            </label>
            <a href="{{ route('password.request') }}" class="font-semibold text-pen">Esqueci a senha</a>
        </div>

        <x-ui.button class="w-full">Entrar</x-ui.button>
    </form>

    @if (Route::has('register'))
        <p class="mt-8 text-center text-sm text-ink-soft">
            Primeira vez aqui? <a href="{{ route('register') }}" class="font-semibold text-pen">Criar conta</a>
        </p>
    @endif
</x-layouts.guest>
