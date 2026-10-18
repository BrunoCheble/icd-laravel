<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
        <!-- font awesome -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        {{-- The side menu folded to its icons (remembered on this device), set before the page shows --}}
        <script>
            try { if (localStorage.getItem('sidebar-collapsed') === '1') document.documentElement.classList.add('sidebar-collapsed'); } catch (error) {}
        </script>

        <style>
            /* Alpine parts stay hidden until Alpine has started, so nothing hidden by x-show flashes on load. */
            [x-cloak] { display: none !important; }

            /* ---- Frame: side menu (one section per module), top bar with the page's title, content ---- */
            :root { --sidebar-width: 15.5rem; --sidebar-folded: 4.25rem; --app-accent: #4f46e5; --app-accent-soft: #eef2ff; }
            html.sidebar-collapsed { --sidebar-width: var(--sidebar-folded); }
            body { background: #f4f5f7; }
            .app-shell { min-height: 100vh; }
            .app-body { min-height: 100vh; margin-left: var(--sidebar-width); transition: margin-left .2s ease; display: flex; flex-direction: column; }

            .app-sidebar { position: fixed; inset: 0 auto 0 0; z-index: 40; width: var(--sidebar-width); display: flex; flex-direction: column; background: #fff; border-right: 1px solid #e5e7eb; transition: width .2s ease, transform .2s ease; }
            .app-sidebar-brand { display: flex; align-items: center; justify-content: space-between; gap: .5rem; height: 4rem; padding: 0 .9rem; border-bottom: 1px solid #f1f2f4; }
            .app-brand-link { display: flex; align-items: center; gap: .6rem; min-width: 0; color: #111827; text-decoration: none; }
            .app-logo { height: 2.25rem; width: auto; flex: none; }
            .app-brand-name { font-weight: 700; font-size: .95rem; line-height: 1.15; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
            .app-sidebar-close { display: none; border: 0; background: none; color: #6b7280; padding: .35rem .5rem; border-radius: .4rem; cursor: pointer; }
            .app-sidebar-nav { flex: 1; overflow-y: auto; padding: .75rem .6rem; }
            .app-nav-section + .app-nav-section { margin-top: 1rem; }
            .app-nav-title { margin: 0 0 .3rem; padding: 0 .65rem; font-size: .68rem; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: #9ca3af; white-space: nowrap; }
            .app-nav-item { position: relative; display: flex; align-items: center; gap: .7rem; padding: .55rem .65rem; border-radius: .55rem; color: #4b5563; font-size: .9rem; font-weight: 500; text-decoration: none; white-space: nowrap; transition: background .15s, color .15s; }
            .app-nav-item i { width: 1.15rem; text-align: center; color: #9ca3af; font-size: .95rem; transition: color .15s; }
            .app-nav-item:hover { background: #f3f4f6; color: #111827; }
            .app-nav-item:hover i { color: #4b5563; }
            .app-nav-item.is-active { background: var(--app-accent-soft); color: var(--app-accent); font-weight: 600; }
            .app-nav-item.is-active i { color: var(--app-accent); }
            .app-nav-item.is-active::before { content: ''; position: absolute; left: -.6rem; top: .45rem; bottom: .45rem; width: 3px; border-radius: 0 3px 3px 0; background: var(--app-accent); }
            .app-nav-label { overflow: hidden; text-overflow: ellipsis; }
            .app-nav-badge { margin-left: auto; min-width: 1.25rem; padding: 0 .4rem; border-radius: 999px; background: var(--app-accent); color: #fff; font-size: .7rem; font-weight: 700; line-height: 1.25rem; text-align: center; }
            .app-sidebar-collapse { display: flex; align-items: center; gap: .7rem; margin: .5rem .6rem .75rem; padding: .5rem .65rem; border: 0; border-radius: .55rem; background: none; color: #9ca3af; font-size: .8rem; cursor: pointer; white-space: nowrap; }
            .app-sidebar-collapse i { width: 1.15rem; text-align: center; }
            .app-sidebar-collapse:hover { background: #f3f4f6; color: #4b5563; }
            .app-sidebar-backdrop { position: fixed; inset: 0; z-index: 35; background: rgba(17, 24, 39, .4); }

            /* Folded: only the icons (each one's name in its tooltip) */
            html.sidebar-collapsed .app-brand-name, html.sidebar-collapsed .app-nav-label, html.sidebar-collapsed .app-nav-title { display: none; }
            html.sidebar-collapsed .app-sidebar-brand { justify-content: center; padding: 0; }
            html.sidebar-collapsed .app-nav-item, html.sidebar-collapsed .app-sidebar-collapse { justify-content: center; padding-left: 0; padding-right: 0; }
            html.sidebar-collapsed .app-nav-section + .app-nav-section { margin-top: .6rem; padding-top: .6rem; border-top: 1px solid #f1f2f4; }
            html.sidebar-collapsed .app-nav-badge { position: absolute; top: .15rem; right: .3rem; min-width: 1rem; padding: 0 .25rem; font-size: .6rem; line-height: 1rem; }

            .app-topbar { display: flex; align-items: center; gap: .75rem; min-height: 4rem; padding: .5rem 1.5rem; background: #fff; border-bottom: 1px solid #e5e7eb; }
            .app-topbar-menu { display: none; border: 0; background: none; color: #4b5563; font-size: 1.15rem; padding: .4rem .55rem; border-radius: .45rem; cursor: pointer; }
            .app-topbar-menu:hover { background: #f3f4f6; }
            .app-topbar-title { flex: 1; min-width: 0; }
            .app-topbar-title h2 { margin: 0; font-size: 1.15rem; font-weight: 700; color: #111827; overflow: hidden; text-overflow: ellipsis; }
            .app-user { display: inline-flex; align-items: center; gap: .55rem; padding: .3rem .55rem .3rem .3rem; border: 1px solid transparent; border-radius: 999px; background: none; color: #374151; font-size: .875rem; font-weight: 500; cursor: pointer; }
            .app-user:hover { background: #f3f4f6; }
            .app-avatar { display: inline-flex; align-items: center; justify-content: center; width: 2rem; height: 2rem; border-radius: 999px; background: var(--app-accent); color: #fff; font-size: .8rem; font-weight: 700; }
            .app-main { flex: 1; }

            /* Phones and tablets: the menu is a drawer opened from the top bar */
            @media (max-width: 1023px) {
                .app-body, html.sidebar-collapsed .app-body { margin-left: 0; }
                .app-sidebar, html.sidebar-collapsed .app-sidebar { width: 16.5rem; transform: translateX(-100%); box-shadow: none; }
                .app-sidebar.is-open { transform: none; box-shadow: 0 20px 50px rgba(0, 0, 0, .25); }
                html.sidebar-collapsed .app-brand-name, html.sidebar-collapsed .app-nav-label, html.sidebar-collapsed .app-nav-title { display: revert; }
                html.sidebar-collapsed .app-nav-item { justify-content: flex-start; padding-left: .65rem; padding-right: .65rem; }
                html.sidebar-collapsed .app-sidebar-brand { justify-content: space-between; padding: 0 .9rem; }
                html.sidebar-collapsed .app-nav-section + .app-nav-section { margin-top: 1rem; padding-top: 0; border-top: 0; }
                html.sidebar-collapsed .app-nav-badge { position: static; min-width: 1.25rem; padding: 0 .4rem; font-size: .7rem; line-height: 1.25rem; }
                .app-sidebar-collapse { display: none; }
                .app-sidebar-close { display: inline-flex; }
                .app-topbar-menu { display: inline-flex; }
                .app-topbar { padding: .4rem .75rem; min-height: 3.25rem; }
            }

            /* Phones: a more compact frame (page title and page spacing), so content starts higher. */
            @media (max-width: 639px) {
                .app-topbar-title h2 { font-size: 1rem; line-height: 1.35; }
                .app-user-name { display: none; }
                .app-main .py-12 { padding-top: .75rem; padding-bottom: .75rem; }
                .app-main .p-4.shadow { padding: .75rem; }
                /* Page action buttons side by side instead of one per line */
                .app-main .mt-4.sm\:ml-16 { display: flex; flex-wrap: wrap; gap: .5rem; margin-top: .75rem; }
                .app-main .mt-4.sm\:ml-16 > * { margin-top: 0 !important; }
                .app-main .mt-4.sm\:ml-16 > a.block, .app-main .mt-4.sm\:ml-16 > button.block { display: inline-flex; align-items: center; gap: .35rem; width: auto; }
                /* Lower table rows */
                .app-main td.py-4 { padding-top: .6rem; padding-bottom: .6rem; }
                .app-main th.py-3, .app-main th.py-3\.5 { padding-top: .5rem; padding-bottom: .5rem; }
                .app-main .mt-8 { margin-top: 1rem; }
                .app-main .mt-6 { margin-top: .75rem; }
            }
        </style>
    </head>
    <body class="font-sans antialiased">
        <div class="app-shell" x-data="{
                menuOpen: false,
                collapsed: document.documentElement.classList.contains('sidebar-collapsed'),
                toggleCollapsed() {
                    this.collapsed = !this.collapsed;
                    document.documentElement.classList.toggle('sidebar-collapsed', this.collapsed);
                    try { localStorage.setItem('sidebar-collapsed', this.collapsed ? '1' : '0'); } catch (error) {}
                },
            }" @keydown.escape.window="menuOpen = false">
            @include('layouts.navigation')

            <div class="app-body">
                <header class="app-topbar">
                    <button type="button" class="app-topbar-menu" @click="menuOpen = true" aria-label="{{ __('Open menu') }}"><i class="fa-solid fa-bars"></i></button>
                    <div class="app-topbar-title">
                        {{ $header ?? '' }}
                    </div>
                    <x-dropdown align="right" width="48">
                        <x-slot name="trigger">
                            <button type="button" class="app-user">
                                <span class="app-avatar">{{ mb_strtoupper(mb_substr(Auth::user()->name, 0, 1)) }}</span>
                                <span class="app-user-name">{{ Auth::user()->name }}</span>
                                <i class="fa-solid fa-chevron-down" style="font-size: .65rem; color: #9ca3af;"></i>
                            </button>
                        </x-slot>
                        <x-slot name="content">
                            <x-dropdown-link :href="route('profile.edit')">
                                <i class="fa-regular fa-user fa-fw me-1"></i> {{ __('Profile') }}
                            </x-dropdown-link>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <x-dropdown-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">
                                    <i class="fa-solid fa-arrow-right-from-bracket fa-fw me-1"></i> {{ __('Log Out') }}
                                </x-dropdown-link>
                            </form>
                        </x-slot>
                    </x-dropdown>
                </header>

                <!-- Page Content -->
                <main class="app-main">
                    {{ $slot }}
                </main>
            </div>
        </div>
    </body>
</html>
