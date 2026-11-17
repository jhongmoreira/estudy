<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>Sem conexão · {{ config('app.name') }}</title>
    <script>try{var t=localStorage.getItem('theme');if(t==='dark'||((!t||t==='system')&&matchMedia('(prefers-color-scheme: dark)').matches))document.documentElement.classList.add('dark')}catch(e){}</script>
    @vite(['resources/css/app.css'])
</head>
<body class="grid min-h-dvh place-items-center bg-paper px-6 text-center text-ink antialiased">
    <div class="max-w-xs">
        <span class="mx-auto mb-5 grid size-16 place-items-center rounded-2xl bg-marker/40 text-marker-ink"><x-icon name="wifi-off" class="size-8" /></span>
        <h1 class="text-2xl font-bold">Sem conexão</h1>
        <p class="mt-2 text-ink-soft">Se você estava com o cronômetro ligado, ele continua contando — o tempo é guardado no servidor.</p>
        <button onclick="location.reload()" class="tap mt-6 rounded-2xl bg-pen px-5 py-3 font-semibold text-white dark:text-[#14161c]">Tentar novamente</button>
    </div>
</body>
</html>
