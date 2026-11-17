<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>@yield('title') · Estudy</title>
    <link rel="icon" href="/icons/logo.svg" type="image/svg+xml">
    <style>
        :root { --paper: #f7f4ee; --ink: #1f2a44; --soft: #5b6478; --pen: #3047c9; --on-pen: #fff; --marker: #ffe066; --rule: #e4ded1; }
        @media (prefers-color-scheme: dark) { :root { --paper: #14161c; --ink: #e8eaf0; --soft: #9aa3b5; --pen: #8ea0ff; --on-pen: #14161c; --marker: #c9a400; --rule: #2e3340; } }
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100dvh; display: grid; place-items: center; padding: 24px; background: var(--paper); color: var(--ink);
               font-family: ui-sans-serif, system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif; text-align: center;
               background-image: repeating-linear-gradient(to bottom, transparent 0 27px, var(--rule) 27px 28px); }
        .box { max-width: 360px; }
        .code { font-family: ui-rounded, system-ui, sans-serif; font-size: 72px; font-weight: 800; line-height: 1; margin: 0 0 12px;
                background: linear-gradient(transparent 58%, var(--marker) 58%, var(--marker) 90%, transparent 90%); display: inline-block; padding: 0 .1em; }
        h1 { margin: 0; font-size: 22px; font-family: ui-rounded, system-ui, sans-serif; }
        p { color: var(--soft); line-height: 1.5; }
        a { display: inline-block; margin-top: 12px; padding: 12px 22px; border-radius: 16px; background: var(--pen); color: var(--on-pen); font-weight: 600; text-decoration: none; }
    </style>
</head>
<body>
    <div class="box">
        <div class="code">@yield('code')</div>
        <h1>@yield('title')</h1>
        <p>@yield('message')</p>
        <a href="@yield('link', '/')">@yield('button', 'Voltar para o início')</a>
    </div>
</body>
</html>
