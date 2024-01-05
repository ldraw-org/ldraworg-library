@props(['title'])
<!DOCTYPE html>
<html lang="en">
    <head>
        <title>{{ $title }}</title>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
        <meta name="csrf-token" content="{{ csrf_token() }}" />
        @stack('meta')
        <link rel="shortcut icon" href="{{asset('/images/LDraw_Green_64x64.png')}}" type="image/x-icon">
        @livewireStyles
        @vite('resources/css/app.css')
        @stack('css')
    </head>
  <body class="bg-gradient-to-r from-[#D4D4D4] via-[#F4F4F4] via-[#FFFFFF] via-[#F4F4F4] to-[#D4D4D4]">
    <div class="container mx-auto p-4 space-y-4">
        @env('local')
            <x-message centered type="warning">
                <x-slot:header>
                    You are on the BETA LDraw.org Library Site.
                </x-slot:header>
                For the live version go here: <a href="https://library.ldraw.org">http://library.ldraw.org</a>
            </x-message>        
        @endenv
      
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
        <nav class="bg-white rounded">   
            {{$menu ?? ''}}
        </nav>    
      <div class="ui hidden fitted clearing divider"></div>
      <div class="ui right floated compact fitted basic segment inline">
        @auth
          Welcome {{Auth::user()->name}} :: 
          
          <div class="relative w-10 h-10">
            <x-fas-file class='absolute left-0 top-0' />
            <x-fas-comment class='absolute bottom-0 left-0 w-5 h-5 fill-blue-500' />
          </div>
          <a href="{{route('dashboard.index')}}">User Dashboard</a>
          @can('admin.view-dashboard')
            :: <a href="{{route('admin.dashboard')}}">Admin Dashboard</a>
          @endcan
        @endauth
      </div>
      
      <div class="ui basic segment breadcrumb">
        <div class="section"><a href="https://www.ldraw.org">LDraw.org</a></div>
        @isset($breadcrumbs)
          <x-breadcrumb-item item="Library" />
          {{$breadcrumbs}}
        @else   
          <x-breadcrumb-item class="active" item="Library" />
        @endisset
      </div>
      
  
      <div class="ui segment main-content">
         {{ $slot ?? '' }}
      </div>
    
  
      <div class="ui basic segment footer">
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
    @vite('resources/js/app.js')
    @stack('scripts')
    @livewireScripts
  </body>
</html>
