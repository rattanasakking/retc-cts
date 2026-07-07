<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="retccts">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'RETC-CTS') }}</title>

        <x-pwa-head />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-gradient-to-br from-primary/5 via-base-200 to-secondary/10">
        <div class="min-h-screen flex flex-col items-center justify-center gap-6 px-4 py-10">
            <a href="/" wire:navigate class="flex flex-col items-center gap-2">
                <span class="flex h-14 w-14 items-center justify-center rounded-2xl bg-primary text-primary-content shadow-lg shadow-primary/30">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422A12.083 12.083 0 0112 20.055 12.083 12.083 0 015.84 10.578L12 14z" />
                    </svg>
                </span>
                <span class="text-lg font-bold">RETC-CTS</span>
                <span class="text-xs text-base-content/60 -mt-1">Smart Career Tracking System</span>
            </a>

            <div class="card w-full max-w-md bg-base-100 shadow-xl">
                <div class="card-body">
                    {{ $slot }}
                </div>
            </div>
        </div>
    </body>
</html>
