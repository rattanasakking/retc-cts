@props(['title' => null])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="retccts">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $title ?? 'ค้นหาข้อมูลนักศึกษา' }} | RETC Smart Career Tracking System</title>

        <x-pwa-head />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body class="min-h-screen bg-base-200 flex flex-col">
        <header class="navbar bg-base-100 border-b border-base-300 px-4 lg:px-8">
            <div class="flex-1">
                <a href="{{ route('public.student-search') }}" wire:navigate class="flex items-center gap-2 font-bold">
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-primary text-primary-content">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422A12.083 12.083 0 0112 20.055 12.083 12.083 0 015.84 10.578L12 14z" />
                        </svg>
                    </span>
                    RETC-CTS
                </a>
            </div>
            <div class="flex-none">
                <a href="{{ route('login') }}" wire:navigate class="btn btn-ghost btn-sm gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l3 3m0 0l-3 3m3-3H2.25" />
                    </svg>
                    <span class="hidden sm:inline">เข้าสู่ระบบ (เจ้าหน้าที่)</span>
                    <span class="sm:hidden">เข้าสู่ระบบ</span>
                </a>
            </div>
        </header>

        <main class="flex-1 px-4 py-8 lg:py-12">
            <div class="max-w-3xl mx-auto w-full">
                {{ $slot }}
            </div>
        </main>

        <footer class="text-center text-xs text-base-content/50 py-6">
            RETC Smart Career Tracking System
        </footer>

        @livewireScripts
    </body>
</html>
