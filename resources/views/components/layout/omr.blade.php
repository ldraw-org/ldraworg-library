<x-layout.base favicon_color="Black">
    <x-slot:title>{{$title}}</x-slot>
    <x-slot:rightlogo>{{asset('/images/banners/omr.png')}}</x-slot>
    <x-slot:menu>
      <x-menu.omr />
    </x-slot>
    {{ $slot ?? '' }}
</x-layout.base>    
  