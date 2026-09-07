{{-- Mobile tab bar (Skylearn): the five destinations staff reach most, with
     the drawer behind the last tab so nothing in the sidebar becomes
     unreachable on a phone. Hidden from lg: up, where the sidebar is pinned. --}}
@php
    $tabs = [
        ['route' => 'dashboard', 'label' => 'ภาพรวม', 'icon' => 'M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25'],
        ['route' => 'students.index', 'label' => 'นักศึกษา', 'icon' => 'M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z'],
        ['route' => 'students.recently-updated', 'label' => 'ล่าสุด', 'icon' => 'M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z'],
    ];

    if (auth()->check() && auth()->user()->hasRole(\App\Enums\UserRole::Admin, \App\Enums\UserRole::Executive, \App\Enums\UserRole::DepartmentHead)) {
        $tabs[] = ['route' => 'reports.career-status', 'label' => 'รายงาน', 'icon' => 'M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0115 18.257V17.25m6-12V15a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 15V5.25m18 0A2.25 2.25 0 0018.75 3H5.25A2.25 2.25 0 003 5.25m18 0V12a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 12V5.25'];
    } elseif (auth()->check() && auth()->user()->hasRole(\App\Enums\UserRole::Teacher)) {
        $tabs[] = ['route' => 'career-statuses.create', 'label' => 'บันทึกงาน', 'icon' => 'M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21'];
    }
@endphp

<nav class="bottom-nav lg:hidden fixed bottom-0 inset-x-0 z-40 bg-base-100 border-t border-base-300 pb-[env(safe-area-inset-bottom)]">
    <div class="grid grid-cols-{{ count($tabs) + 1 }}">
        @foreach ($tabs as $tab)
            @php $active = request()->routeIs($tab['route']); @endphp
            <a href="{{ route($tab['route']) }}" wire:navigate
               class="flex flex-col items-center justify-center gap-1 py-2 text-[0.6875rem] font-semibold {{ $active ? 'text-primary' : 'text-base-content/55' }}">
                <span @class(['flex items-center justify-center rounded-xl px-4 py-1', 'bg-primary/10' => $active])>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $tab['icon'] }}" />
                    </svg>
                </span>
                {{ $tab['label'] }}
            </a>
        @endforeach

        <label for="app-drawer"
               class="flex flex-col items-center justify-center gap-1 py-2 text-[0.6875rem] font-semibold text-base-content/55 cursor-pointer">
            <span class="flex items-center justify-center rounded-xl px-4 py-1">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                </svg>
            </span>
            เมนู
        </label>
    </div>
</nav>
