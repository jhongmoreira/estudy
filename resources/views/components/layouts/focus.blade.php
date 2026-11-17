@props(['title' => null])
{{-- Layout sem navegação: onboarding e (na etapa 3) o modo foco do cronômetro --}}
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <x-layouts.head :title="$title" />
</head>
<body class="min-h-dvh bg-paper text-ink antialiased">
    <main class="mx-auto flex min-h-dvh w-full max-w-md flex-col px-5 pt-safe pb-safe">
        {{ $slot }}
    </main>
    <x-ui.toasts />
</body>
</html>
