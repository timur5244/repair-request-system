<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>@yield('title', 'Заявки в ремонтную службу')</title>
    <style>
        :root { --bg:#0b1020; --card:#121a33; --text:#eaf0ff; --muted:#aab6e6; --accent:#6aa6ff; --danger:#ff6a6a; --ok:#3ddc97; }
        * { box-sizing: border-box; }
        body { margin:0; font-family: ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Arial; background: linear-gradient(180deg, #070b17, var(--bg)); color: var(--text); }
        a { color: var(--accent); text-decoration: none; }
        .container { max-width: 1000px; margin: 0 auto; padding: 24px 16px; }
        .topbar { display:flex; justify-content:space-between; align-items:center; gap: 12px; margin-bottom: 18px; }
        .card { background: rgba(18,26,51,.9); border: 1px solid rgba(106,166,255,.18); border-radius: 14px; padding: 16px; box-shadow: 0 12px 40px rgba(0,0,0,.35); }
        .muted { color: var(--muted); }
        .grid { display:grid; gap: 12px; }
        .grid-2 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        label { display:block; font-size: 13px; color: var(--muted); margin: 10px 0 6px; }
        input, textarea, select { width: 100%; padding: 10px 12px; border-radius: 10px; border: 1px solid rgba(255,255,255,.12); background: rgba(6,9,20,.7); color: var(--text); outline: none; }
        textarea { min-height: 110px; resize: vertical; }
        .row { display:flex; gap: 10px; align-items:center; flex-wrap: wrap; }
        .btn { display:inline-flex; align-items:center; justify-content:center; gap:8px; padding: 10px 12px; border-radius: 10px; border: 1px solid rgba(106,166,255,.35); background: rgba(106,166,255,.12); color: var(--text); cursor:pointer; }
        .btn:hover { background: rgba(106,166,255,.18); }
        .btn-danger { border-color: rgba(255,106,106,.35); background: rgba(255,106,106,.10); }
        .btn-danger:hover { background: rgba(255,106,106,.16); }
        .badge { display:inline-flex; align-items:center; padding: 3px 10px; border-radius: 999px; border: 1px solid rgba(255,255,255,.12); font-size: 12px; }
        .badge-new { border-color: rgba(106,166,255,.35); }
        .badge-done { border-color: rgba(61,220,151,.35); color: var(--ok); }
        .badge-canceled { border-color: rgba(255,106,106,.35); color: var(--danger); }
        table { width:100%; border-collapse: collapse; }
        th, td { padding: 10px 8px; border-bottom: 1px solid rgba(255,255,255,.08); vertical-align: top; }
        th { text-align:left; font-size: 12px; color: var(--muted); font-weight: 600; }
        .error { color: var(--danger); font-size: 13px; margin-top: 10px; }
    </style>
</head>
<body>
<div class="container">
    <div class="topbar">
        <div>
            <div style="font-weight:700; letter-spacing:.2px;">Заявки в ремонтную службу</div>
            <div class="muted" style="font-size:13px;">Учебный прототип (SQLite, упрощённый вход)</div>
        </div>

        @auth
            <div class="row">
                <div class="row" style="gap:8px;">
                    @if(auth()->user()->role === 'dispatcher')
                        <x-nav-link :href="route('dispatcher.dashboard')" :active="request()->routeIs('dispatcher.*')">Панель диспетчера</x-nav-link>
                    @endif
                    @if(auth()->user()->role === 'master')
                        <x-nav-link :href="route('master.dashboard')" :active="request()->routeIs('master.*')">Панель мастера</x-nav-link>
                    @endif
                    <x-nav-link :href="route('requests.index')" :active="request()->routeIs('requests.*')">Заявки</x-nav-link>
                    <x-nav-link :href="route('requests.create')" :active="request()->routeIs('requests.create')">Новая заявка</x-nav-link>
                </div>

                <div class="muted" style="font-size:13px;">{{ auth()->user()->name }} ({{ auth()->user()->role }})</div>
                <form method="post" action="{{ route('logout') }}">
                    @csrf
                    <button class="btn btn-danger" type="submit">Выйти</button>
                </form>
            </div>
        @endauth
    </div>

    @yield('content')
</div>
</body>
</html>

