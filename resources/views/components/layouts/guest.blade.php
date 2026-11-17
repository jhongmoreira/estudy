@props(['title' => null, 'heading' => null])
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <x-layouts.head :title="$title" />
</head>
<body class="min-h-dvh bg-paper text-ink antialiased">
    <div class="grid min-h-dvh lg:grid-cols-2">
        {{-- Lado "caderno" (desktop) --}}
        <aside class="ruled relative hidden flex-col justify-between border-r border-rule bg-sheet p-12 lg:flex">
            <div class="flex items-center gap-3">
                <img src="/icons/logo.svg" alt="" class="size-10 rounded-xl">
                <span class="font-display text-2xl font-bold">Estudy</span>
            </div>
            <div class="max-w-md">
                <p class="font-display text-4xl font-bold leading-tight">
                    Seus estudos, do seu jeito: <span class="highlight">cursos, cronômetro, revisões</span> e metas num só lugar.
                </p>
                <p class="mt-4 text-ink-soft">Graduação, pós, curso livre ou aquele livro que você quer terminar.</p>
            </div>
            <p class="text-sm text-ink-soft">Feito para quem aprende todo dia.</p>
        </aside>

        <main class="mx-auto flex w-full max-w-sm flex-col justify-center px-5 py-10 pt-safe pb-safe">
            <div class="mb-8 lg:hidden">
                <img src="/icons/logo.svg" alt="" class="mb-4 size-14 rounded-2xl">
                <span class="font-display text-3xl font-bold"><span class="highlight">Estudy</span></span>
            </div>
            @if ($heading)
                <h1 class="font-display text-2xl font-bold">{{ $heading }}</h1>
            @endif
            @isset($subtitle)
                <p class="mt-1 text-ink-soft">{{ $subtitle }}</p>
            @endisset

            @if (session('status'))
                <div class="mt-6 rounded-xl bg-pen-soft px-4 py-3 text-sm text-pen">{{ session('status') }}</div>
            @endif

            <div class="mt-6">
                {{ $slot }}
            </div>
        </main>
    </div>
</body>
</html>
