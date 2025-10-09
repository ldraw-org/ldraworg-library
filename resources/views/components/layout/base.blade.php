@props(['title', 'favicon_color' => 'Green'])
<!DOCTYPE html>
<html lang="en">
    <head>
        <title>{{ $title }}</title>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
        <meta name="csrf-token" content="{{ csrf_token() }}" />
        @stack('meta')
        <link rel="shortcut icon" href="{{asset('/images/LDraw_' . $favicon_color . '_64x64.png')}}" type="image/x-icon">
        @filamentStyles
        @vite('resources/css/app.css')
        @stack('css')
    </head>
  <body class="bg-gradient-to-r from-[#D4D4D4] via-[#F4F4F4] via-[#FFFFFF] via-[#F4F4F4] to-[#D4D4D4]">
    <div class="container mx-auto p-4 space-y-4">
        @env('local')
            <x-message centered icon type="warning">
                <x-slot:header>
                    You are on the BETA LDraw.org Library Site.
                </x-slot:header>
                For the live version go here: <a class="underline decoration-dotted hover:decoration-solid hover:text-gray-500" href="https://library.ldraw.org">https://library.ldraw.org</a>
            </x-message>
        @endenv
        {{ $messages ?? '' }}
        <div class="grid grid-cols-2 justify-stretch items-center">
            <div class="justify-self-start">
                <a href="https://www.ldraw.org">
                    <img id="main-logo" src="{{asset('/images/banners/main.png')}}">
                </a>
            </div>
            @isset($rightlogo)
                <div class="justify-self-end">
                    <img src="{{$rightlogo}}">
                </div>
            @endisset
        </div>
        <nav class="flex flex-col md:flex-row bg-white rounded-lg border border-gray-300">
            {{$menu ?? ''}}
            <livewire:search.menu-item />
        </nav>
        <div class="grid grid-cols-1 md:grid-cols-2 justify-stretch items-center">
            <div class="invisible md:visible justify-self-start">
                <div class="flex flex-row items-center">
                    <a href="https://www.ldraw.org">LDraw.org</a>
                    @isset($breadcrumbs)
                        <x-breadcrumb-item item="Library" />
                        {{$breadcrumbs}}
                    @else
                        <x-breadcrumb-item active item="Library" />
                    @endisset
                </div>
            </div>
            <div class="justify-self-end">
                @auth
                    Welcome {{Auth::user()->realname}}
                    <a class="underline decoration-dotted hover:decoration-solid hover:text-gray-500" href="{{route('dashboard.index')}}">User Dashboard</a>
                    @can(\App\Enums\Permission::AdminDashboardView)
                        :: <a class="underline decoration-dotted hover:decoration-solid hover:text-gray-500" href="{{route('admin.index')}}">Admin Dashboard</a>
                    @endcan
                @endauth
            </div>
        </div>


      <div class="bg-white rounded p-2">
         {{ $slot ?? '' }}
      </div>


      <div class="flex flex-col p-2">
        <p>
          Website copyright &copy;2003-{{date_format(now(),"Y")}} LDraw.org, see
          <a href="/legal-info">Legal Info</a> for details.
        </p>
        <p>
          LDraw is a completely unofficial, community run free CAD system which
          represents official parts produced by the LEGO company.
        </p>
        <p>
          LDraw&trade; is a trademark owned and licensed by the Estate of James Jessiman<br>
          LEGO&reg; is a registered trademark of the LEGO Group, which does not sponsor,
          endorse, or authorize this site. Visit the official Lego website at
          <a href="https://www.lego.com" target="_blank">http://www.lego.com</a>
        </p>
      </div>
    </div>

    <div>
        @livewire('notifications')
    </div>
    @filamentScripts
    @vite('resources/js/app.js')
    @stack('scripts')
  </body>
</html>
