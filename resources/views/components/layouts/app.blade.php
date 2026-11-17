@props(['title' => null])
@php
    $nav = [
        ['route' => 'home', 'label' => 'Hoje', 'icon' => 'home', 'exact' => true],
        ['route' => 'courses', 'label' => 'Cursos', 'icon' => 'book'],
        ['route' => 'reviews', 'label' => 'Revisões', 'icon' => 'review'],
        ['route' => 'more', 'label' => 'Mais', 'icon' => 'grid'],
    ];
    $user = auth()->user();
@endphp
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <x-layouts.head :title="$title" />
</head>
<body class="min-h-dvh bg-paper text-ink antialiased">
    <div class="lg:flex">
        {{-- Sidebar (desktop): caderno aberto, com o botão de estudar em destaque --}}
        @persist('sidebar')
        <aside class="sticky top-0 hidden h-dvh w-64 shrink-0 flex-col border-r border-rule bg-sheet px-4 py-6 lg:flex">
            <a href="{{ route('home') }}" wire:navigate class="mb-8 flex items-center gap-3 px-2">
                <img src="/icons/logo.svg" alt="" class="size-9 rounded-xl">
                <span class="font-display text-xl font-bold"><span class="highlight">Estudy</span></span>
            </a>

            <a href="{{ route('study') }}" wire:navigate
               class="tap mb-6 flex items-center justify-center gap-2 rounded-2xl bg-pen px-4 py-3 font-semibold text-white shadow-sm hover:bg-pen-strong dark:text-[#14161c]">
                <x-icon name="play" class="size-5" /> Estudar agora
            </a>

            <nav class="flex flex-col gap-1">
                @foreach ($nav as $item)
                    <a href="{{ route($item['route']) }}" wire:navigate
                       @if ($item['exact'] ?? false) wire:current.exact="!bg-pen-soft !text-pen" @else wire:current="!bg-pen-soft !text-pen" @endif
                       class="flex items-center gap-3 rounded-xl px-3 py-2.5 font-medium text-ink-soft hover:bg-sheet-2 hover:text-ink">
                        <x-icon :name="$item['icon']" />
                        {{ $item['label'] }}
                    </a>
                @endforeach
            </nav>

            <a href="{{ route('profile') }}" wire:navigate class="mt-auto flex items-center gap-3 rounded-xl px-2 py-2 hover:bg-sheet-2">
                <x-ui.avatar :user="$user" class="size-9 text-sm" />
                <span class="min-w-0">
                    <span class="block truncate text-sm font-semibold">{{ $user->name }}</span>
                    <span class="block truncate text-xs text-ink-soft">{{ $user->email }}</span>
                </span>
            </a>
        </aside>
        @endpersist

        <div class="min-w-0 flex-1">
            <main class="mx-auto w-full max-w-4xl px-4 pb-36 pt-safe lg:px-8 lg:pb-12">
                {{ $slot }}
            </main>
        </div>
    </div>

    {{-- Barra inferior (celular): Hoje · Cursos · ▶ · Revisões · Mais --}}
    @persist('bottom-nav')
    <nav class="fixed inset-x-0 bottom-0 z-30 border-t border-rule bg-sheet/95 pb-safe backdrop-blur lg:hidden" aria-label="Navegação principal">
        <div class="mx-auto grid h-16 max-w-md grid-cols-5 items-center px-1">
            @foreach ($nav as $i => $item)
                @if ($i === 2)
                    <div class="flex justify-center">
                        <a href="{{ route('study') }}" wire:navigate aria-label="Estudar agora"
                           class="tap -mt-6 grid size-15 place-items-center rounded-2xl bg-pen text-white shadow-lg shadow-[#3047c9]/30 ring-4 ring-paper dark:text-[#14161c]">
                            <x-icon name="play" class="size-7 translate-x-px" />
                        </a>
                    </div>
                @endif
                <a href="{{ route($item['route']) }}" wire:navigate
                   @if ($item['exact'] ?? false) wire:current.exact="!text-pen [&_.dot]:!opacity-100" @else wire:current="!text-pen [&_.dot]:!opacity-100" @endif
                   class="tap flex flex-col items-center gap-0.5 text-[11px] font-medium text-ink-soft">
                    <x-icon :name="$item['icon']" class="size-6" />
                    <span>{{ $item['label'] }}</span>
                    <span class="dot -mt-0.5 h-1 w-4 rounded-full bg-marker opacity-0"></span>
                </a>
            @endforeach
        </div>
    </nav>

    {{-- Instruções de instalação no iPhone --}}
    <x-ui.sheet name="ios-install" title="Instalar no iPhone">
        <ol class="space-y-4 text-sm">
            <li class="flex items-center gap-3"><span class="grid size-8 shrink-0 place-items-center rounded-full bg-pen-soft font-bold text-pen">1</span> <span>Toque em <x-icon name="share" class="inline size-5 text-pen" /> <strong>Compartilhar</strong> na barra do Safari.</span></li>
            <li class="flex items-center gap-3"><span class="grid size-8 shrink-0 place-items-center rounded-full bg-pen-soft font-bold text-pen">2</span> <span>Escolha <strong>Adicionar à Tela de Início</strong>.</span></li>
            <li class="flex items-center gap-3"><span class="grid size-8 shrink-0 place-items-center rounded-full bg-pen-soft font-bold text-pen">3</span> <span>Toque em <strong>Adicionar</strong>. Pronto!</span></li>
        </ol>
    </x-ui.sheet>
    @endpersist

    <x-ui.toasts />
</body>
</html>
