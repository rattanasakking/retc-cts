@props(['title' => null])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="retccts">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $title ?? 'RETC-CTS' }} | RETC Smart Career Tracking System</title>

        <x-pwa-head />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body class="min-h-screen bg-base-200">
        <div
            class="drawer lg:drawer-open"
            x-data="{ collapsed: JSON.parse(localStorage.getItem('retc-sidebar-collapsed') ?? 'false') }"
            x-effect="localStorage.setItem('retc-sidebar-collapsed', JSON.stringify(collapsed))"
        >
            <input id="app-drawer" type="checkbox" class="drawer-toggle" />

            <div class="drawer-content flex flex-col min-h-screen">
                <livewire:layout.navigation />

                @isset($header)
                    <div class="bg-base-100 border-b border-base-300 px-4 py-5 lg:px-8">
                        {{ $header }}
                    </div>
                @endisset

                <main class="flex-1 p-4 lg:p-8">
                    <div class="max-w-7xl mx-auto w-full">
                        {{ $slot }}
                    </div>
                </main>
            </div>

            <div class="drawer-side z-30">
                <label for="app-drawer" aria-label="close sidebar" class="drawer-overlay"></label>

                <aside
                    class="min-h-full w-64 bg-neutral text-neutral-content flex flex-col transition-[width] duration-200"
                    :class="collapsed ? 'lg:w-20' : 'lg:w-64'"
                >
                    {{-- Brand --}}
                    <div class="flex items-center gap-3 px-4 py-5 border-b border-white/10">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-primary text-primary-content">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422A12.083 12.083 0 0112 20.055 12.083 12.083 0 015.84 10.578L12 14z" />
                            </svg>
                        </span>
                        <div x-show="!collapsed" x-transition.opacity class="leading-tight overflow-hidden whitespace-nowrap">
                            <p class="font-bold text-sm">RETC-CTS</p>
                            <p class="text-xs text-neutral-content/60">Career Tracking System</p>
                        </div>
                    </div>

                    {{-- Nav --}}
                    <nav class="sidebar-nav-scroll flex-1 overflow-y-auto px-2 py-4 space-y-6">
                        <div>
                            <p x-show="!collapsed" class="px-3 mb-1 text-xs font-semibold uppercase tracking-wider text-neutral-content/40">ภาพรวม</p>
                            <ul class="menu w-full p-0 gap-1">
                                <li>
                                    <a href="{{ route('dashboard') }}" wire:navigate title="Dashboard"
                                       class="{{ request()->routeIs('dashboard') ? 'active bg-primary text-primary-content' : 'text-neutral-content/80 hover:bg-white/10' }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                                        </svg>
                                        <span x-show="!collapsed" x-transition.opacity>Dashboard</span>
                                    </a>
                                </li>
                            </ul>
                        </div>

                        <div>
                            <p x-show="!collapsed" class="px-3 mb-1 text-xs font-semibold uppercase tracking-wider text-neutral-content/40">ติดตามภาวะการทำงาน</p>
                            <ul class="menu w-full p-0 gap-1">
                                <li>
                                    <a href="{{ route('students.index') }}" wire:navigate title="Students"
                                       class="{{ request()->routeIs('students.index') ? 'active bg-primary text-primary-content' : 'text-neutral-content/80 hover:bg-white/10' }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                                        </svg>
                                        <span x-show="!collapsed" x-transition.opacity>Students</span>
                                    </a>
                                </li>
                                @auth
                                    @if (auth()->user()->hasRole(\App\Enums\UserRole::Admin, \App\Enums\UserRole::DepartmentHead))
                                        <li>
                                            <a href="{{ route('students.import') }}" wire:navigate title="นำเข้าข้อมูลนักศึกษา"
                                               class="{{ request()->routeIs('students.import') ? 'active bg-primary text-primary-content' : 'text-neutral-content/80 hover:bg-white/10' }}">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M7.5 7.5L12 3m0 0l4.5 4.5M12 3v13.5" />
                                                </svg>
                                                <span x-show="!collapsed" x-transition.opacity>นำเข้า CSV</span>
                                            </a>
                                        </li>
                                        <li>
                                            <a href="{{ route('students.trash') }}" wire:navigate title="ถังขยะ - นักศึกษา"
                                               class="{{ request()->routeIs('students.trash') ? 'active bg-primary text-primary-content' : 'text-neutral-content/80 hover:bg-white/10' }}">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                                </svg>
                                                <span x-show="!collapsed" x-transition.opacity>ถังขยะ</span>
                                            </a>
                                        </li>
                                    @endif
                                @endauth
                                <li>
                                    <a class="pointer-events-none opacity-50 text-neutral-content/80" title="Career Paths">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 6.75V15m6-6v8.25m.503 3.498l4.875-2.437c.381-.19.622-.58.622-1.006V4.82c0-.836-.88-1.38-1.628-1.006l-3.869 1.934c-.317.159-.69.159-1.006 0L9.503 3.252a1.125 1.125 0 00-1.006 0L3.622 5.689C3.24 5.88 3 6.27 3 6.695V19.18c0 .836.88 1.38 1.628 1.006l3.869-1.934c.317-.159.69-.159 1.006 0l4.994 2.497c.317.158.69.158 1.006 0z" />
                                        </svg>
                                        <span x-show="!collapsed" x-transition.opacity class="flex-1">Career Paths</span>
                                        <span x-show="!collapsed" class="badge badge-ghost badge-xs">เร็วๆ นี้</span>
                                    </a>
                                </li>
                                @auth
                                    @if (auth()->user()->hasRole(\App\Enums\UserRole::Admin, \App\Enums\UserRole::Teacher, \App\Enums\UserRole::DepartmentHead))
                                        <li>
                                            <a href="{{ route('career-statuses.create') }}" wire:navigate title="ภาวะการมีงานทำ"
                                               class="{{ request()->routeIs('career-statuses.create') ? 'active bg-primary text-primary-content' : 'text-neutral-content/80 hover:bg-white/10' }}">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" />
                                                </svg>
                                                <span x-show="!collapsed" x-transition.opacity>ภาวะการมีงานทำ</span>
                                            </a>
                                        </li>
                                    @else
                                        <li>
                                            <a class="pointer-events-none opacity-50 text-neutral-content/80" title="ภาวะการมีงานทำ">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" />
                                                </svg>
                                                <span x-show="!collapsed" x-transition.opacity class="flex-1">ภาวะการมีงานทำ</span>
                                                <span x-show="!collapsed" class="badge badge-ghost badge-xs">เร็วๆ นี้</span>
                                            </a>
                                        </li>
                                    @endif
                                @endauth
                            </ul>
                        </div>

                        @auth
                            @if (auth()->user()->hasRole(\App\Enums\UserRole::Admin, \App\Enums\UserRole::Executive, \App\Enums\UserRole::DepartmentHead))
                                <div>
                                    <p x-show="!collapsed" class="px-3 mb-1 text-xs font-semibold uppercase tracking-wider text-neutral-content/40">การจัดการ</p>
                                    <ul class="menu w-full p-0 gap-1">
                                        <li>
                                            <a href="{{ route('reports.export') }}" wire:navigate title="ส่งออกรายงาน"
                                               class="{{ request()->routeIs('reports.export') ? 'active bg-primary text-primary-content' : 'text-neutral-content/80 hover:bg-white/10' }}">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                                                </svg>
                                                <span x-show="!collapsed" x-transition.opacity>ส่งออกรายงาน</span>
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            @endif

                            @if (auth()->user()->hasRole(\App\Enums\UserRole::Admin, \App\Enums\UserRole::Teacher, \App\Enums\UserRole::DepartmentHead))
                                <div>
                                    <p x-show="!collapsed" class="px-3 mb-1 text-xs font-semibold uppercase tracking-wider text-neutral-content/40">แจ้งเตือน</p>
                                    <ul class="menu w-full p-0 gap-1">
                                        <li>
                                            <a href="{{ route('notifications.reminders') }}" wire:navigate title="แจ้งเตือนนักศึกษา"
                                               class="{{ request()->routeIs('notifications.reminders') ? 'active bg-primary text-primary-content' : 'text-neutral-content/80 hover:bg-white/10' }}">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                                                </svg>
                                                <span x-show="!collapsed" x-transition.opacity>แจ้งเตือนนักศึกษา</span>
                                            </a>
                                        </li>
                                        @if (auth()->user()->isAdmin())
                                            <li>
                                                <a href="{{ route('notifications.logs') }}" wire:navigate title="ประวัติการแจ้งเตือน"
                                                   class="{{ request()->routeIs('notifications.logs') ? 'active bg-primary text-primary-content' : 'text-neutral-content/80 hover:bg-white/10' }}">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" />
                                                    </svg>
                                                    <span x-show="!collapsed" x-transition.opacity>ประวัติการแจ้งเตือน</span>
                                                </a>
                                            </li>
                                        @endif
                                    </ul>
                                </div>
                            @endif

                            @if (auth()->user()->isAdmin())
                                <div>
                                    <p x-show="!collapsed" class="px-3 mb-1 text-xs font-semibold uppercase tracking-wider text-neutral-content/40">ตั้งค่าระบบ</p>
                                    <ul class="menu w-full p-0 gap-1">
                                        <li>
                                            <a href="{{ route('settings.academic-years') }}" wire:navigate title="ปีการศึกษา"
                                               class="{{ request()->routeIs('settings.academic-years') ? 'active bg-primary text-primary-content' : 'text-neutral-content/80 hover:bg-white/10' }}">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                                                </svg>
                                                <span x-show="!collapsed" x-transition.opacity>ปีการศึกษา</span>
                                            </a>
                                        </li>
                                        <li>
                                            <a href="{{ route('settings.system') }}" wire:navigate title="ข้อมูลระบบ"
                                               class="{{ request()->routeIs('settings.system') ? 'active bg-primary text-primary-content' : 'text-neutral-content/80 hover:bg-white/10' }}">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.28z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                </svg>
                                                <span x-show="!collapsed" x-transition.opacity>ข้อมูลระบบ</span>
                                            </a>
                                        </li>
                                        <li>
                                            <a href="{{ route('settings.users') }}" wire:navigate title="จัดการผู้ใช้งาน"
                                               class="{{ request()->routeIs('settings.users') ? 'active bg-primary text-primary-content' : 'text-neutral-content/80 hover:bg-white/10' }}">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" />
                                                </svg>
                                                <span x-show="!collapsed" x-transition.opacity>จัดการผู้ใช้งาน</span>
                                            </a>
                                        </li>
                                        <li>
                                            <a href="{{ route('settings.backup') }}" wire:navigate title="สำรอง/กู้คืนข้อมูล"
                                               class="{{ request()->routeIs('settings.backup') ? 'active bg-primary text-primary-content' : 'text-neutral-content/80 hover:bg-white/10' }}">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375m16.5 5.625c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125" />
                                                </svg>
                                                <span x-show="!collapsed" x-transition.opacity>สำรอง/กู้คืนข้อมูล</span>
                                            </a>
                                        </li>
                                        <li>
                                            <a href="{{ route('audit-logs.index') }}" wire:navigate title="บันทึกการใช้งานระบบ"
                                               class="{{ request()->routeIs('audit-logs.index') ? 'active bg-primary text-primary-content' : 'text-neutral-content/80 hover:bg-white/10' }}">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3-15H6.75a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 006.75 21h10.5A2.25 2.25 0 0019.5 18.75V9L15.75 3z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 3v4.5A2.25 2.25 0 0018 9.75h4.5" />
                                                </svg>
                                                <span x-show="!collapsed" x-transition.opacity>บันทึกการใช้งานระบบ</span>
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            @endif
                        @endauth
                    </nav>

                    {{-- Collapse toggle (desktop only) --}}
                    <div class="hidden lg:block border-t border-white/10 p-2">
                        <button
                            type="button"
                            @click="collapsed = !collapsed"
                            class="btn btn-ghost btn-sm w-full text-neutral-content/70 hover:bg-white/10"
                            :class="collapsed ? 'justify-center' : 'justify-start gap-2'"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0 transition-transform" :class="collapsed && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M18.75 19.5l-7.5-7.5 7.5-7.5m-6 15L5.25 12l7.5-7.5" />
                            </svg>
                            <span x-show="!collapsed" x-transition.opacity>ย่อเมนู</span>
                        </button>
                    </div>
                </aside>
            </div>
        </div>

        @livewireScripts
    </body>
</html>
