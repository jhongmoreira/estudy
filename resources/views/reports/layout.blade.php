<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<title>@yield('title') · Estudy</title>
<style>
    @page { margin: 30px 34px 40px; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 9.5px; color: #1f2a44; line-height: 1.35; }
    .head { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
    .head td { vertical-align: middle; }
    .logo { width: 30px; height: 30px; }
    h1 { font-size: 17px; margin: 0; }
    .hl { background: #ffe066; padding: 0 3px; }
    .sub { color: #5b6478; font-size: 9px; margin-top: 2px; }
    h2 { font-size: 11.5px; margin: 16px 0 6px; padding-bottom: 3px; border-bottom: 2px solid #e4ded1; }
    h3 { font-size: 10px; margin: 10px 0 4px; }
    .muted { color: #5b6478; }
    .small { font-size: 8px; }
    .cards { width: 100%; border-collapse: separate; border-spacing: 5px 0; margin: 6px -5px 0; }
    .cards td { background: #f7f4ee; padding: 7px 8px; width: 25%; border-radius: 6px; }
    .cards .label { font-size: 7.5px; color: #5b6478; text-transform: uppercase; letter-spacing: .4px; }
    .cards .value { font-size: 14px; font-weight: bold; margin-top: 2px; }
    table.list { width: 100%; border-collapse: collapse; }
    table.list th { text-align: left; font-size: 7.5px; color: #5b6478; text-transform: uppercase; border-bottom: 1px solid #e4ded1; padding: 4px 4px; }
    table.list td { padding: 3.5px 4px; border-bottom: 1px solid #f1ede4; vertical-align: top; }
    table.list tr.total td { font-weight: bold; border-top: 1px solid #e4ded1; border-bottom: 0; }
    .right, table.list th.right, table.list td.right { text-align: right; }
    .center, table.list th.center, table.list td.center { text-align: center; }
    .bar { height: 7px; background: #f1ede4; border-radius: 4px; }
    .bar div { height: 7px; border-radius: 4px; }
    .dot { display: inline-block; width: 7px; height: 7px; border-radius: 4px; margin-right: 3px; }
    .stripe { border-left: 3px solid #3047c9; padding-left: 6px; }
    .tag { display: inline-block; padding: 1px 5px; border-radius: 4px; font-size: 7.5px; font-weight: bold; }
    .ok { background: #d9f2e3; color: #2f855a; } .warn { background: #fff4c2; color: #5c4a00; } .bad { background: #fde3d6; color: #c2410c; } .info { background: #e3e8ff; color: #3047c9; }
    .done { color: #2f855a; } .late { color: #c2410c; }
    .cols { width: 100%; border-collapse: collapse; } .cols > tbody > tr > td { vertical-align: top; width: 50%; }
    .cols > tbody > tr > td:first-child { padding-right: 10px; } .cols > tbody > tr > td:last-child { padding-left: 10px; }
    .footer { position: fixed; bottom: -26px; left: 0; right: 0; font-size: 7.5px; color: #9aa3b5; }
    .footer .pg:after { content: counter(page); }
    .avoid { page-break-inside: avoid; }
</style>
</head>
<body>
    <div class="footer"><table style="width:100%"><tr><td>Estudy · {{ $user->name }} · gerado em {{ now()->format('d/m/Y H:i') }}</td><td class="right">página <span class="pg"></span></td></tr></table></div>
    <table class="head">
        <tr>
            <td style="width: 38px"><img class="logo" src="{{ public_path('icons/icon-192.png') }}" alt=""></td>
            <td>
                <h1>@yield('heading')</h1>
                <div class="sub">@yield('subheading')</div>
            </td>
        </tr>
    </table>
    @yield('content')
</body>
</html>
