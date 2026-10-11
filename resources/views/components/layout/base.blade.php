@props(['title' => '', 'favicon_color' => 'Green', 'menu' => 'library', 'logo' => 'tracker'])

<!DOCTYPE html>
<html lang="en">
    <head>
        <title>{{ $title ? "$title | " . config('app.name') : config('app.name') }}</title>
        <meta charset="utf-8">
        <meta name="csrf-token" content="{{ csrf_token() }}" />
        @stack('meta')
        @head
        <link rel="icon" type="image/png" href="{{asset('/images/LDraw_' . $favicon_color . '_64x64.png')}}" >
        @filamentStyles
        @vite('resources/css/app.css')
        @stack('css')
    </head>
  <body class="bg-linear-to-r from-[#D4D4D4] via-white  to-[#D4D4D4]">
    <div class="p-4 space-y-2">
        <x-message.beta-notice />
        {{ $messages ?? '' }}
        <x-layout.site.header :$logo :$menu />
        <main class="rounded-lg bg-white p-2">
            {{ $slot }}
        </main>
        <x-layout.site.footer />
    </div>
    <div>
        @livewire('notifications')
    </div>
    @filamentScripts
    @vite('resources/js/app.js')
    @stack('scripts')
  </body>
</html>
