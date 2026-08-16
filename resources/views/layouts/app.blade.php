<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Моя пивоварня')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
<div id="app" class="layout">
    <aside class="sidebar">
        <div class="brand"><span class="brand-ico">🍺</span><span>Моя пивоварня</span></div>
        <nav class="nav">
            <a href="/" class="{{ request()->is('/') ? 'router-link-active' : '' }}">
                <span class="nav-icon">🏠</span>Главная
            </a>
            <a href="/equipment" class="{{ request()->is('equipment') ? 'router-link-active' : '' }}">
                <span class="nav-icon">📦</span>Оборудование
            </a>
            <a href="/stock" class="{{ request()->is('stock') ? 'router-link-active' : '' }}">
                <span class="nav-icon">📦</span>Склад
            </a>
            <a href="/recipes" class="{{ request()->is('recipes*') ? 'router-link-active' : '' }}">
                <span class="nav-icon">🍺</span>Рецепты
            </a>
            <a href="/brews" class="{{ request()->is('brews*') ? 'router-link-active' : '' }}">
                <span class="nav-icon">🔥</span>Варки
            </a>
            <a href="/settings" class="{{ request()->is('settings') ? 'router-link-active' : '' }}">
                <span class="nav-icon">⚙️</span>Настройки
            </a>
        </nav>
        <div class="sidebar-foot">Личный трекер пивоварения</div>
    </aside>
    <main class="main">
        @yield('content')
    </main>
</div>
@stack('scripts')
</body>
</html>
