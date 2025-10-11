<x-layout.base favicon_color="Orange">
    <x-slot:title>{{$title}}</x-slot>
    <x-slot:rightlogo>{{asset('/images/banners/documentation.png')}}</x-slot>
    <x-slot:menu>
      <x-menu.library />
    </x-slot>
    {{ $slot ?? '' }}
</x-layout.base>    
  