@props(['title' => '', 'favicon_color' => 'Green', 'menu' => 'library', 'logo' => 'tracker'])
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
    <div class="mx-auto p-4 space-y-2">
        @env('local')
            <x-message centered icon type="warning">
                <x-slot:header>
                    You are on the BETA LDraw.org Library Site.
                </x-slot:header>
                For the live version go here: <a class="underline decoration-dotted hover:decoration-solid hover:text-gray-500" href="https://library.ldraw.org">https://library.ldraw.org</a>
            </x-message>
        @endenv
      
        {{ $messages ?? '' }}
      
        <div class="flex flex-col lg:flex-row space-y-2 lg:space-x-2 justify-items-end">
            <div class="w-fit order-first flex-none">
                <a href="{{route('index')}}">
                    <img id="main-logo" src="{{asset('/images/banners/'. $logo . '.png')}}">
                </a>
            </div>
            <div class="flex flex-row space-x-2 items-end w-full">
                <div class="md:grow">
                    <x-dynamic-component component="{{ 'menu.' . $menu }}"  />
                </div>
                <div class="self-center lg:self-end mb-1">
                    @livewire('search.menu-item')
                </div>
                @auth
                    <div class="self-center lg:self-end lg:mb-1">
                        <x-menu.user-icon />
                    </div>
                @endauth
            </div>
        </div>
      <div class="main-content rounded-lg bg-white p-2">
         {{ $slot ?? '' }}
      </div>

      <div class="flex flex-col text-xs">
        <p>
          Website copyright &copy;2003-{{date_format(now(),"Y")}} LDraw.org, see
          <a href="https://www.ldraw.org/docs-main/licenses/legal-info.html">Legal Info</a> for details.<br>
          LDraw is a completely unofficial, community run free CAD system which
          represents official parts produced by the LEGO company.<br>
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
