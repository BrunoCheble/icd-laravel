{{-- Side menu: one section per module (see App\Support\Menu); on phones it opens as a drawer --}}
<aside class="app-sidebar" :class="{ 'is-open': menuOpen }" aria-label="{{ __('Menu') }}">
    <div class="app-sidebar-brand">
        <a href="{{ route('dashboard') }}" class="app-brand-link">
            <x-application-logo class="app-logo" />
            <span class="app-brand-name">{{ config('app.name') }}</span>
        </a>
        <button type="button" class="app-sidebar-close" @click="menuOpen = false" aria-label="{{ __('Close menu') }}"><i class="fa-solid fa-xmark"></i></button>
    </div>

    <nav class="app-sidebar-nav">
        @foreach ($menu as $section)
            <div class="app-nav-section">
                @if ($section['label'])
                    <p class="app-nav-title">{{ __($section['label']) }}</p>
                @endif
                @foreach ($section['items'] as $item)
                    <a href="{{ $item['url'] }}" @class(['app-nav-item', 'is-active' => $item['active']]) title="{{ __($item['label']) }}"
                        @if ($item['active']) aria-current="page" @endif>
                        <i class="{{ $item['icon'] }} fa-fw"></i>
                        <span class="app-nav-label">{{ __($item['label']) }}</span>
                        @if ($item['badge'] > 0)
                            <span class="app-nav-badge">{{ $item['badge'] }}</span>
                        @endif
                    </a>
                @endforeach
            </div>
        @endforeach
    </nav>

    <button type="button" class="app-sidebar-install" x-show="canInstall" x-cloak @click="install()" title="{{ __('Install the app') }}">
        <i class="fa-solid fa-mobile-screen-button"></i>
        <span class="app-nav-label">{{ __('Install the app') }}</span>
    </button>
    <button type="button" class="app-sidebar-collapse" @click="toggleCollapsed()"
        :title="collapsed ? @js(__('Expand menu')) : @js(__('Collapse menu'))" :aria-label="collapsed ? @js(__('Expand menu')) : @js(__('Collapse menu'))">
        <i class="fa-solid" :class="collapsed ? 'fa-angles-right' : 'fa-angles-left'"></i>
        <span class="app-nav-label">{{ __('Collapse menu') }}</span>
    </button>
</aside>
<div class="app-sidebar-backdrop" x-show="menuOpen" x-cloak x-transition.opacity @click="menuOpen = false"></div>
