<x-layout.base>
    <x-slot:title>{{$title}}</x-slot>
    <x-slot:rightlogo>{{asset('/images/banners/omr.png')}}</x-slot>
    <x-slot:menu>
      <x-menu.omr />
    </x-slot>
    <x-slot:breadcrumbs>
        @isset($breadcrumbs)
            <x-breadcrumb-item item="Official Model Repository" />
            {{$breadcrumbs}}
        @else   
            <x-breadcrumb-item class="active" item="Official Model Repository" />
        @endisset
    </x-slot>      
    {{ $slot ?? '' }}
</x-layout.base>    
  