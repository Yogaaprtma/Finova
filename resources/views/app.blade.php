<!DOCTYPE html>
@php
    $isInitialAuthBoot = in_array($page['component'] ?? '', ['auth/Login', 'auth/Register'], true)
        && empty((array) ($page['props']['errors'] ?? []))
        && empty($page['props']['status'] ?? null);
@endphp
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" @if($isInitialAuthBoot) data-auth-boot @endif @class(['dark' => ($appearance ?? 'system') == 'dark'])>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        {{-- Inline script to detect system dark mode preference and apply it immediately --}}
        <script>
            (function() {
                const appearance = '{{ $appearance ?? "system" }}';

                if (appearance === 'system') {
                    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

                    if (prefersDark) {
                        document.documentElement.classList.add('dark');
                    }
                }

                if (document.documentElement.hasAttribute('data-auth-boot')) {
                    window.setTimeout(function() {
                        document.documentElement.removeAttribute('data-auth-boot');
                    }, 1500);
                }
            })();
        </script>

        {{-- Inline style to set the HTML background color based on our theme in app.css --}}
        <style>
            html {
                background-color: #F8F9FA;
            }

            html.dark {
                background-color: #0F1117;
            }

            html[data-auth-boot],
            html[data-auth-boot] body {
                background-color: #060b18 !important;
            }

            html[data-auth-boot] #app {
                opacity: 0;
            }
        </style>

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        @fonts

        @vite(['resources/css/app.css', 'resources/js/app.ts', "resources/js/pages/{$page['component']}.vue"])
        <x-inertia::head>
            <title>{{ config('app.name', 'Laravel') }}</title>
        </x-inertia::head>
    </head>
    <body class="font-sans antialiased">
        <x-inertia::app />
    </body>
</html>
